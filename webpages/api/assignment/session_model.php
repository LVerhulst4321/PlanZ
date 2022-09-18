<?php

class SessionSchedule {

}

class Session {

    public $sessionId;
    public $title;
    public $programGuideDescription;
    public $notesForProgramStaff;
    public $sessionSchedule;

    public static function findById($db, $sessionId) {
        $query = <<<EOD
        SELECT
            S.title, S.progguiddesc, S.notesforprog
        FROM
                      Sessions S
        WHERE
            S.sessionid=?;
EOD;
    
        $stmt = mysqli_prepare($db, $query);
        mysqli_stmt_bind_param($stmt, "i", $sessionId);
        $session = null;
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            while ($row = mysqli_fetch_object($result)) {
                $session = new Session();
                $session->sessionId = $sessionId;
                $session->title = $row->title;
                $session->programGuideDescription = $row->progguiddesc;
                $session->notesForProgramStaff = $row->notesforprog;
            }
            mysqli_stmt_close($stmt);
            return $session;
        } else {
            throw new DatabaseSqlException("Query could not be executed: $query");
        }
    }
    function asArray() {
        return array("sessionId" => $this->sessionId, 
            "title" => $this->title, 
            "programGuideDescription" => $this->programGuideDescription, 
            "notesForProgramStaff" => $this->notesForProgramStaff);
    }
}

?>