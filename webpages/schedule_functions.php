<?php

require_once(__DIR__ . "/db_exceptions.php");

class ScheduledSession {
    public $title;
    public $sessionid;
    public $progguiddesc;
    public $starttime;
    public $starttime_unformatted;
    public $endtime_unformatted;
    public $duration;
    public $roomname;
    public $roomid;
    public $trackname;
    public $hashtag;
    public $participants;
    public $pubsNumber;

    function participantById($badgeid) {
        $result = null;
        foreach ($this->participants as $p) {
            if ($p->badgeid == $badgeid) {
                $result = $p;
                break;
            }
        }
        return $result;
    }

    public static function findAllScheduledSessionsWithParticipants($db) {
        $ConStartDatim = CON_START_DATIM;
        $query1 =<<<EOD
    SELECT
            S.sessionid, S.title, S.progguiddesc, R.roomname, SCH.roomid, PS.pubstatusname,
            DATE_FORMAT(ADDTIME('$ConStartDatim',SCH.starttime),'%a %l:%i %p') AS starttime,
            ADDTIME('$ConStartDatim',ADDTIME(SCH.starttime, S.duration)) as endtimeraw,
            ADDTIME('$ConStartDatim',SCH.starttime) as starttimeraw,
            DATE_FORMAT(S.duration,'%i') AS durationmin, DATE_FORMAT(S.duration,'%k') AS durationhrs,
            T.trackname, KC.kidscatname, S.hashtag, S.pubsno
        FROM
                 Sessions S
            JOIN Schedule SCH USING (sessionid)
            JOIN Rooms R USING (roomid)
            JOIN PubStatuses PS USING (pubstatusid)
            JOIN Tracks T USING (trackid)
            JOIN KidsCategories KC USING (kidscatid)
        WHERE
            R.is_online = 'N'
        ORDER BY
            SCH.starttime, R.roomname;
EOD;

        $stmt = mysqli_prepare($db, $query1);

        $sessions = array();
        $sessionsById = array();
        if (mysqli_stmt_execute($stmt)) {
            $resultSet = mysqli_stmt_get_result($stmt);
            while ($row = mysqli_fetch_object($resultSet)) {
                $session = new ScheduledSession();
                $session->title = $row->title ?? '';
                $session->sessionid = $row->sessionid ?? '';
                $session->progguiddesc = $row->progguiddesc ?? '';
                $session->roomname = $row->roomname ?? '';
                $session->roomid = $row->roomid;
                $session->trackname = $row->trackname ?? '';
                $session->hashtag = $row->hashtag ?? '';
                $session->starttime = $row->starttime ?? '';
                $session->starttime_unformatted = DateTime::createFromFormat( "Y-m-d H:i:s", $row->starttimeraw );
                $session->endtime_unformatted = DateTime::createFromFormat( "Y-m-d H:i:s", $row->endtimeraw );
                $session->pubsNumber = $row->pubsno;
                $session->participants = array();


                $sessions[] = $session;
                $sessionsById[$session->sessionid] = $session;
            }
            $stmt->close();
        } else {
            throw new DatabaseSqlException("Could not process query: $query1");
        }


        $query2 =<<<'EOD'
    SELECT
            SCH.sessionid, P.pubsname, P.badgeid, POS.moderator, PRO.pronounname
        FROM
                      Schedule SCH
                 JOIN ParticipantOnSession POS USING (sessionid)
                 JOIN Participants P USING (badgeid)
                 JOIN CongoDump C USING (badgeid)
            LEFT JOIN ParticipantDetails PD USING (badgeid)
            LEFT JOIN Pronouns PRO USING (pronounid)
        ORDER BY
            SCH.sessionid, POS.moderator DESC,
            IF(instr(P.pubsname,C.lastname)>0,C.lastname,substring_index(P.pubsname,' ',-1)),
            C.firstname;
EOD;

        $stmt = mysqli_prepare($db, $query2);
        if (mysqli_stmt_execute($stmt)) {
            $resultSet = mysqli_stmt_get_result($stmt);
            while ($row = mysqli_fetch_assoc($resultSet)) {
                $session = $sessionsById[$row["sessionid"]];
                if ($session) {
                    $participant = new ScheduledParticipant();
                    $participant->pubsname = $row["pubsname"] ?? '';
                    $participant->moderator = $row["moderator"] ?? '';
                    $participant->badgeid = $row["badgeid"] ?? '';
                    $participant->pronouns = $row["pronouns"] ?? '';

                    $session->participants[] = $participant;
                }
            }
            $stmt->close();
        } else {
            throw new DatabaseSqlException("Could not process query: $query1");
        }

        $sessionsByBadgeId = array();
        for ($i = (count($sessions) - 1); $i >=0 ; $i--) {

            $s = $sessions[$i];

            foreach ($s->participants as $p) {
                if (array_key_exists($p->badgeid, $sessionsByBadgeId)) {
                    $next = $sessionsByBadgeId[$p->badgeid];
                    $p->nextSession = $next;
                }
                $sessionsByBadgeId[$p->badgeid] = $s;
            }
        }

        return $sessions;
    }
}

class ScheduledParticipant {
    public $pubsname;
    public $badgeid;
    public $moderator;
    public $pronouns;

    public $nextSession;
}

function render_sessions_as_xml($sessions) {
    $xml = new DomDocument("1.0", "UTF-8");
    $doc = $xml -> createElement("doc");
    $doc = $xml -> appendChild($doc);

    foreach ($sessions as $s) {
        $panel = $xml -> createElement("session");
        $panel->setAttribute("title", $s->title ?: '');
        $panel->setAttribute("progguiddesc", $s->progguiddesc ?: '');
        $panel->setAttribute("roomname", $s->roomname ?: '');
        $panel->setAttribute("trackname", $s->trackname ?: '');
        $panel->setAttribute("hashtag", $s->hashtag ?: '');
        $panel->setAttribute("starttime", $s->starttime ?: '');

        foreach ($s->participants as $p) {
            $particpant = $xml -> createElement("participant");
            $particpant->setAttribute("pubsname", $p->pubsname ?: '');
            $particpant->setAttribute("badgeid", $p->badgeid ?: '');
            $particpant->setAttribute("moderator", $p->moderator ?: '');
            $particpant->setAttribute("pronouns", $p->pronouns ?: '');

            $panel -> appendChild($particpant);

            if (!is_null($p->nextSession)) {
                $next = $xml -> createElement("nextSession");
                $next->setAttribute("title", $p->nextSession->title ?: '');
                $next->setAttribute("progguiddesc", $p->nextSession->progguiddesc ?: '');
                $next->setAttribute("roomname", $p->nextSession->roomname ?: '');
                $next->setAttribute("trackname", $p->nextSession->trackname ?: '');
                $next->setAttribute("starttime", $p->nextSession->starttime ?: '');
                $particpant -> appendChild($next);
            }
        }

        $doc -> appendChild($panel);
    }
    return $xml;
}

function get_scheduled_events_with_participants_as_xml($db) {
    return render_sessions_as_xml(ScheduledSession::findAllScheduledSessionsWithParticipants($db));
}


?>