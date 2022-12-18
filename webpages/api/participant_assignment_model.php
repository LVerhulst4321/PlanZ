<?php

class ParticipantSessionInterestResponse {
    public $comment;
    public $willModerate;
    public $rank;
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

    public static function findAssignmentForSessionByBadgeId($db, $sessionId, $badgeId) {
        $assignments = ParticipantAssignment::findAssignmentsForSession($db, $sessionId);
        foreach ($assignments as $a) {
            if ($a->badgeId === $badgeId) {
                return $a;
            }
        }
        return null;
    }

    public static function findAssignmentsForSession($db, $sessionId) {
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
            PSI.willmoderate
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
                    $interest->willmoderate = $row->willmoderate ? true : false;
                    $assignment->interestResponse = $interest;
                }

                $assignments[] = $assignment;
            }
            mysqli_stmt_close($stmt);
            return $assignments;
        } else {
            throw new DatabaseSqlException("Query could not be executed: $query");
        }
    }

    function asArray() {
        $result = array("badgeId" => $this->badgeId,
            "name" => $this->name->getBadgeName(),
            "textBio" => $this->textBio,
            "moderator" => $this->moderator,
            "registered" => $this->registered,
            "confirmed" => $this->confirmed,
            "links" => array("avatar" => $this->avatarSrc)
        );
        if ($this->interestResponse != null) {
            $result["interestResponse"] = array("rank" => $this->interestResponse->rank,
                "comments" => $this->interestResponse->comments,
                "willModerate" => $this->interestResponse->willmoderate
            );
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

    public static function updateModeratorStatus($db, $participantAssignment) {
        mysqli_begin_transaction($db);
        try {
            if ($participantAssignment->moderator) {
                $query = <<<EOD
                UPDATE ParticipantOnSession
                SET moderator = 0
                WHERE sessionId = ?
                AND badgeid != ?
                AND moderator = 1;
EOD;
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
            $stmt = mysqli_prepare($db, $query);
            $moderator = $participantAssignment->moderator ? 1 : 0;
            mysqli_stmt_bind_param($stmt, "iis", $moderator,
                $participantAssignment->sessionId, $participantAssignment->badgeId);
            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_close($stmt);
            } else {
                throw new DatabaseSqlException("Update could not be executed: $query");
            }
            mysqli_commit($db);
        } catch (Exception $e) {
            mysqli_rollback($db);
            throw $e;
        }
    }
};

?>
