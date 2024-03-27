<?php

class ParticipantSessionInterestResponse {
    public $comments;
    public $willModerate;
    public $rank;
}

class ParticipantAssignmentConflict {
    public $sessionId;
    public $title;
}

class ParticipantAssignment {

    public $badgeId;
    public $name;
    public $moderator;
    public $avatarSrc;
    public $registered;
    public $confirmed;
    public $interestResponse;
    public $textBio;
    public $sessionId;
    public $willingnessToBeParticipant;
    public $conflicts = array();

    public static function findAssignmentForSessionByBadgeId($db, $sessionId, $badgeId) {
        $assignments = ParticipantAssignment::findAssignmentsForSession($db, $sessionId);
        foreach ($assignments as $a) {
            if ($a->badgeId === $badgeId) {
                return $a;
            }
        }
        return null;
    }

    public static function findAssignmentsForSession($db, $sessionId, $sessionSchedule = null) {
        $query = <<<EOD
        SELECT
            POS.badgeid,
            COALESCE(POS.moderator, 0) AS moderator,
            P.pubsname,
            CD.badgename,
            CD.firstname,
            CD.lastname,
            CD.regtype,
            POS.confirmed,
            P.approvedphotofilename,
            P.bio,
            PSI.rank,
            PSI.comments,
            PSI.willmoderate,
            P.interested
        FROM
                      ParticipantOnSession POS
                 JOIN Participants P ON P.badgeid = POS.badgeid
                 JOIN CongoDump CD ON CD.badgeid = POS.badgeid
            LEFT JOIN ParticipantSessionInterest PSI ON (POS.badgeid = PSI.badgeid and POS.sessionid = PSI.sessionid)
        WHERE
            POS.sessionid=?
        ORDER BY moderator DESC, badgename;
EOD;

        $stmt = mysqli_prepare($db, $query);
        mysqli_stmt_bind_param($stmt, "i", $sessionId);
        $assignments = [];
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            while ($row = mysqli_fetch_object($result)) {
                $assignments[] = ParticipantAssignment::toModel($row, $sessionId);
            }
            mysqli_stmt_close($stmt);
            return ParticipantAssignment::appendSessionConflicts($db, $assignments, $sessionId, $sessionSchedule);
        } else {
            throw new DatabaseSqlException("Query could not be executed: $query");
        }
    }

    private static function appendSessionConflicts($db, $assignments, $sessionId, $sessionSchedule) {
        if ($assignments != null && count($assignments) > 0 && $sessionSchedule != null) {

            $temp = [];
            $badgeIdList = "";
            foreach ($assignments as $a) {
                if (strlen($badgeIdList) > 0) {
                    $badgeIdList .= ", ";
                }
                $badgeIdList .= ("'" . $db->real_escape_string($a->badgeId) . "'");
                $temp[$a->badgeId] = $a;
            }


            $query = <<<EOD
            SELECT
                POS.badgeid,
                POS.sessionid,
                  S.title
            FROM
                        ParticipantOnSession POS
                    JOIN Sessions S ON S.sessionid = POS.sessionid
                    JOIN Schedule SCH ON POS.sessionid = SCH.sessionid
            WHERE
                POS.sessionid != ?
            AND (
                    ( CAST(? as time) <= SCH.starttime AND CAST(? as time) > SCH.starttime) OR
                    ( CAST(? as time) <= ADDTIME(SCH.starttime, S.duration) AND CAST(? as time) > ADDTIME(SCH.starttime, S.duration)) OR
                    ( CAST(? as time) >= SCH.starttime AND CAST(? as time) <= ADDTIME(SCH.starttime, S.duration))
                )
            AND POS.badgeid in ($badgeIdList);
EOD;

            $stmt = mysqli_prepare($db, $query);
            mysqli_stmt_bind_param($stmt, "issssss", $sessionId, $sessionSchedule->startTimeAsTime, $sessionSchedule->endTimeAsTime,
                $sessionSchedule->startTimeAsTime, $sessionSchedule->endTimeAsTime, $sessionSchedule->startTimeAsTime, $sessionSchedule->endTimeAsTime);

            if (mysqli_stmt_execute($stmt)) {
                $result = mysqli_stmt_get_result($stmt);
                while ($row = mysqli_fetch_object($result)) {
                    $badgeId = $row->badgeid;

                    $conflict = new ParticipantAssignmentConflict();
                    $conflict->sessionId = $sessionId;
                    $conflict->title = $row->title;
                    $a = $temp[$badgeId];
                    $a->conflicts[] = $conflict;
                }

                mysqli_stmt_close($stmt);
            } else {
                throw new DatabaseSqlException("Query could not be executed: $query");
            }
        }

        return $assignments;
    }

    public static function findCandidateAssigneesForSession($db, $sessionId) {
        $query = <<<EOD
        SELECT
            P.badgeid,
            P.pubsname,
            CD.badgename,
            CD.firstname,
            CD.lastname,
            CD.regtype,
            P.approvedphotofilename,
            P.bio,
            PSI.rank,
            PSI.comments,
            PSI.willmoderate,
            P.interested
        FROM ParticipantSessionInterest PSI
        JOIN Participants P ON P.badgeid = PSI.badgeid
        JOIN CongoDump CD ON CD.badgeid = PSI.badgeid
        WHERE PSI.sessionid=?
          AND ((PSI.rank IS NOT NULL
          AND PSI.rank < 6) OR (PSI.willmoderate = 1))
          AND P.badgeid NOT IN (
                select badgeid from ParticipantOnSession POS WHERE POS.sessionid = ?)
        ORDER BY badgename;
EOD;

        $stmt = mysqli_prepare($db, $query);
        mysqli_stmt_bind_param($stmt, "ii", $sessionId, $sessionId);
        $assignments = [];
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            while ($row = mysqli_fetch_object($result)) {
                $assignments[] = ParticipantAssignment::toModel($row, $sessionId);
            }
            mysqli_stmt_close($stmt);
            return $assignments;
        } else {
            throw new DatabaseSqlException("Query could not be executed: $query");
        }
    }

    public static function findOtherCandidateAssigneesForSession($db, $sessionId, $queryString) {
        $lowerQueryString = '%' . mb_strtolower($queryString) . '%';
        $query = <<<EOD
        SELECT
            P.badgeid,
            P.pubsname,
            CD.badgename,
            CD.firstname,
            CD.lastname,
            CD.regtype,
            P.approvedphotofilename,
            P.bio,
            P.sortedpubsname,
            PSI.rank,
            PSI.comments,
            PSI.willmoderate,
            P.interested
        FROM Participants P
        JOIN CongoDump CD USING(badgeid)
        LEFT OUTER JOIN ParticipantSessionInterest PSI ON (P.badgeid = PSI.badgeid AND PSI.sessionId = ?)
        WHERE (P.interested = 1 OR P.interested is NULL)
        AND P.badgeid NOT IN (
            select badgeid from ParticipantOnSession POS WHERE POS.sessionid = ?)
        AND (P.sortedpubsname like ? OR lower(CD.badgename) like ? OR lower(CD.firstname) like ? OR lower(CD.lastname) like ?)
        ORDER BY sortedpubsname
        LIMIT 50;
EOD;

        $stmt = mysqli_prepare($db, $query);
        mysqli_stmt_bind_param($stmt, "iissss", $sessionId, $sessionId, $lowerQueryString, $lowerQueryString, $lowerQueryString, $lowerQueryString);
        $assignments = [];
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            while ($row = mysqli_fetch_object($result)) {
                $assignments[] = ParticipantAssignment::toModel($row, $sessionId);
            }
            mysqli_stmt_close($stmt);
            return $assignments;
        } else {
            throw new DatabaseSqlException("Query could not be executed: $query");
        }
    }

    private static function toModel($row, $sessionId) {
        $name = new PersonName();
        $name->firstName = $row->firstname;
        $name->lastName = $row->lastname;
        $name->badgeName = $row->badgename;
        $name->pubsName = $row->pubsname;

        $assignment = new ParticipantAssignment();
        $assignment->badgeId = $row->badgeid;
        $assignment->name = $name;
        $assignment->moderator = $row->moderator ? true : false;
        $assignment->confirmed = $row->confirmed == 'Y';
        $assignment->textBio = $row->bio;
        $assignment->sessionId = $sessionId;

        $interested = "Unknown";
        if ($row->interested == 1) {
            $interested = "Yes";
        } else if ($row->interested == 2) {
            $interested = "No";
        }

        $assignment->willingnessToBeParticipant = $interested;

        if ($row->approvedphotofilename) {
            $assignment->avatarSrc = PHOTO_PUBLIC_DIRECTORY . '/' . $row->approvedphotofilename;
        } else {
            $assignment->avatarSrc = PHOTO_PUBLIC_DIRECTORY . '/' . PHOTO_DEFAULT_IMAGE;
        }
        $assignment->registered = ($row->regtype) ? true : false;
        if ($row->rank != null || $row->comments != null || $row->willmoderate != null) {
            $interest = new ParticipantSessionInterestResponse();
            $interest->rank = $row->rank;
            $interest->comments = $row->comments;
            $interest->willModerate = $row->willmoderate ? true : false;
            $assignment->interestResponse = $interest;
        }
        return $assignment;
    }

    function asArray() {
        $result = array("badgeId" => $this->badgeId,
            "name" => $this->name->getBadgeName(),
            "textBio" => $this->textBio,
            "moderator" => $this->moderator,
            "registered" => $this->registered,
            "confirmed" => $this->confirmed,
            "willingnessToBeParticipant" => $this->willingnessToBeParticipant,
            "links" => array("avatar" => $this->avatarSrc)
        );
        if ($this->interestResponse != null) {
            $result["interestResponse"] = array("rank" => $this->interestResponse->rank,
                "comments" => $this->interestResponse->comments,
                "willModerate" => $this->interestResponse->willmoderate
            );
        }
        if (count($this->conflicts) > 0) {
            $temp = array();
            foreach ($this->conflicts as $c) {
                $temp[] = array("sessionId" => $c->sessionId,
                    "title" => $c->title);
            }
            $result["conflicts"] = $temp;
        }
        return $result;
    }

    public static function toJsonArray($participantAssignments) {
        $result = [];
        foreach ($participantAssignments as $a) {
            $result[] = $a->asArray();
        }
        return $result;
    }

    public static function removeAssignment($db, $participantAssignment, $authentication) {
        $changedBy = $authentication->getBadgeId();
        $query = <<<EOD
        DELETE FROM ParticipantOnSession
        WHERE sessionId = ?
        AND badgeid = ?;
EOD;

        $historyQuery = <<<EOD
        INSERT INTO participant_on_session_history
        (`badgeid`, `sessionid`, `change_by_badgeid`, `change_type`, `moderator`)
        values (?, ?, ?, 'remove_assignment', ?);
EOD;

        $db->begin_transaction();
        try {
            $stmt = mysqli_prepare($db, $query);
            mysqli_stmt_bind_param($stmt, "is", $participantAssignment->sessionId, $participantAssignment->badgeId);
            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_close($stmt);
            } else {
                throw new DatabaseSqlException("Delete could not be executed: $query");
            }

            $stmt = mysqli_prepare($db, $historyQuery);
            mysqli_stmt_bind_param($stmt, "sisi", $participantAssignment->badgeId, $participantAssignment->sessionId,
                $changedBy, $participantAssignment->moderator);
            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_close($stmt);
            } else {
                throw new DatabaseSqlException("Insert could not be executed: $historyQuery");
            }

            $db->commit();
        } catch (Exception $e) {
            $db->rollback();
            throw $e;
        }
    }

    public static function insertAssignment($db, $sessionId, $badgeId, $authentication) {
        $changedBy = $authentication->getBadgeId();

        $query = <<<EOD
        INSERT INTO ParticipantOnSession (sessionid, badgeid)
        VALUES (?, ?);
EOD;

        $historyQuery = <<<EOD
        INSERT INTO participant_on_session_history
        (`badgeid`, `sessionid`, `change_by_badgeid`, `change_type`, `moderator`)
        values (?, ?, ?, 'insert_assignment', 0);
EOD;
        $db->begin_transaction();
        try {

            $stmt = mysqli_prepare($db, $query);
            mysqli_stmt_bind_param($stmt, "is", $sessionId, $badgeId);
            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_close($stmt);
            } else {
                throw new DatabaseSqlException("Insert could not be executed: $query");
            }

            $stmt = mysqli_prepare($db, $historyQuery);
            mysqli_stmt_bind_param($stmt, "sis", $badgeId, $sessionId, $changedBy);
            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_close($stmt);
            } else {
                throw new DatabaseSqlException("Insert could not be executed: $historyQuery");
            }

            $db->commit();
        } catch (Exception $e) {
            $db->rollback();
            throw $e;
        }
    }

    public static function updateModeratorStatus($db, $participantAssignment, $authentication) {

        $changedBy = $authentication->getBadgeId();
        mysqli_begin_transaction($db);
        error_log("Badge id" . $authentication->getBadgeId() . ' ' . $participantAssignment->sessionId . ' ' . $participantAssignment->badgeId);
        try {
            if ($participantAssignment->moderator) {
                $historyQuery = <<<EOD
                INSERT INTO participant_on_session_history
                (`badgeid`, `sessionid`, `moderator`, `change_by_badgeid`, `change_type`)
                SELECT badgeid, sessionid, 0, ?, 'remove_moderator'
                FROM ParticipantOnSession
                WHERE sessionId = ?
                AND badgeid != ?
                AND moderator = 1;
EOD;

                $query = <<<EOD
                UPDATE ParticipantOnSession
                SET moderator = 0
                WHERE sessionId = ?
                AND badgeid != ?
                AND moderator = 1;
EOD;

                $stmt = mysqli_prepare($db, $historyQuery);
                mysqli_stmt_bind_param($stmt, "sis", $changedBy,
                    $participantAssignment->sessionId, $participantAssignment->badgeId);
                if (mysqli_stmt_execute($stmt)) {
                    mysqli_stmt_close($stmt);
                } else {
                    throw new DatabaseSqlException("Insert could not be executed: $historyQuery");
                }

                $stmt = mysqli_prepare($db, $query);
                mysqli_stmt_bind_param($stmt, "is", $participantAssignment->sessionId, $participantAssignment->badgeId);
                if (mysqli_stmt_execute($stmt)) {
                    mysqli_stmt_close($stmt);
                } else {
                    throw new DatabaseSqlException("Update could not be executed: $query");
                }
            }

            $query = <<<EOD
            UPDATE ParticipantOnSession
            SET moderator = ?
            WHERE sessionId = ?
            AND badgeid = ?;
EOD;

            $historyQuery = <<<EOD
            INSERT INTO participant_on_session_history
            (`badgeid`, `sessionid`, `change_by_badgeid`, `change_type`, `moderator`)
            values (?, ?, ?, ?, ?);
EOD;

            $changeType = $participantAssignment->moderator ? 'assign_moderator' : 'remove_moderator';

            $stmt = mysqli_prepare($db, $query);
            mysqli_stmt_bind_param($stmt, "iis", $participantAssignment->moderator,
                $participantAssignment->sessionId, $participantAssignment->badgeId);
            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_close($stmt);
            } else {
                throw new DatabaseSqlException("Update could not be executed: $query");
            }

            $stmt = mysqli_prepare($db, $historyQuery);
            mysqli_stmt_bind_param($stmt, "sissi", $participantAssignment->badgeId, $participantAssignment->sessionId,
                $changedBy, $changeType, $participantAssignment->moderator);
            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_close($stmt);
            } else {
                throw new DatabaseSqlException("Insert could not be executed: $historyQuery");
            }

            mysqli_commit($db);
        } catch (Exception $e) {
            mysqli_rollback($db);
            throw $e;
        }
    }
};

?>
