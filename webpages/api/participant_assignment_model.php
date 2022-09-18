<?php

class ParticipantAssignment {

    public $badgeId;
    public $name;
    public $moderator;
    public $avatarSrc;
    public $registered;
    public $confirmed;

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
            P.approvedphotofilename
        FROM
                      ParticipantOnSession POS
                 JOIN Participants P ON P.badgeid = POS.badgeid
                 JOIN CongoDump CD ON CD.badgeid = POS.badgeid
        WHERE
            POS.sessionid=?;
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
                if ($row->approvedphotofilename) {
                    $assignment->avatarSrc = PHOTO_PUBLIC_DIRECTORY . '/' . $row->approvedphotofilename;
                } else {
                    $assignment->avatarSrc = PHOTO_PUBLIC_DIRECTORY . '/' . PHOTO_DEFAULT_IMAGE;
                }
                $assignment->registered = ($row->regtype) ? true : false;
                $assignments[] = $assignment;
            }
            mysqli_stmt_close($stmt);
            return $assignments;
        } else {
            throw new DatabaseSqlException("Query could not be executed: $query");
        }
    }

    function asArray() {
        return array("badgeId" => $this->badgeId, 
            "name" => $this->name->getBadgeName(), 
            "moderator" => $this->moderator,
            "registered" => $this->registered,
            "confirmed" => $this->confirmed,
            "links" => array("avatar" => $this->avatarSrc)
        );
    }

    public static function toJsonArray($participantAssignments) {
        $result = [];
        foreach ($participantAssignments as $a) {
            $result[] = $a->asArray();
        }
        return $result;
    }
};

?>
