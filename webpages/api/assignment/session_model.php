<?php

class SessionSchedule {
    public $roomName;
    public $startTime;
    public $endTime;
    public $isOnline;
}

class Session {

    public $sessionId;
    public $title;
    public $trackName;
    public $programGuideDescription;
    public $notesForProgramStaff;
    public $sessionSchedule;
    /* What we call "participants" for this session */
    public $participantLabel;

    public static function findById($db, $sessionId) {
        $CON_START_DATIM = CON_START_DATIM;
        if (DISPLAY_24_HOUR_TIME)
            $timeFormat = '%H:%i';
        else
            $timeFormat = '%l:%i %p';

        $query = <<<EOD
        SELECT
            S.title, S.progguiddesc, S.notesforprog, S.participantlabel, T.trackname, R.roomname, R.is_online,
            DATE_FORMAT(ADDTIME('$CON_START_DATIM', SCH.starttime),'%a $timeFormat') AS starttime,
            DATE_FORMAT(ADDTIME('$CON_START_DATIM', ADDTIME(SCH.starttime, S.duration)),'$timeFormat') AS endtime,
            left(S.duration, 5) AS duration
        FROM
            Sessions S
        JOIN Tracks T USING (trackid)
        LEFT OUTER JOIN
            Schedule SCH USING (sessionid)
        LEFT OUTER JOIN
            Rooms R USING (roomid)
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
                $session->trackName = $row->trackname;
                $session->participantLabel = $row->participantlabel;

                if ($row->roomname) {
                    $schedule = new SessionSchedule();
                    $schedule->roomName = $row->roomname;
                    $schedule->isOnline = $row->is_online == 'Y' ? true : false;
                    $schedule->startTime = $row->starttime;
                    $schedule->endTime = $row->endtime;
                    $session->sessionSchedule = $schedule;
                }
            }
            mysqli_stmt_close($stmt);
            return $session;
        } else {
            throw new DatabaseSqlException("Query could not be executed: $query");
        }
    }
    function asJson() {
        $result = array("sessionId" => $this->sessionId,
            "title" => $this->title,
            "programGuideDescription" => $this->programGuideDescription,
            "notesForProgramStaff" => $this->notesForProgramStaff,
            "track" => array("name" => $this->trackName),
            "participantLabel" => $this->participantLabel);
        if ($this->sessionSchedule) {
            $result["schedule"] = array(
                "room" => array(
                    "name" => $this->sessionSchedule->roomName,
                    "isOnline" => $this->sessionSchedule->isOnline
                ),
                "startTime" => $this->sessionSchedule->startTime,
                "endTime" => $this->sessionSchedule->endTime,
            );
        }
        return $result;
    }
}

?>