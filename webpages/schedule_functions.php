<?php

require_once("db_functions.php");

class ScheduledSession {
    public $title;
    public $sessionid;
    public $progguiddesc;
    public $starttime;
    public $starttime_unformatted;
    public $duration;
    public $roomname;
    public $roomid;
    public $trackname;
    public $hashtag;
    public $participants;

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
        $panel->setAttribute("title", isset($s->starttime) ? $s->title : '');
        $panel->setAttribute("progguiddesc", isset($s->progguiddesc) ? $s->progguiddesc : '');
        $panel->setAttribute("roomname", isset($s->roomname) ? $s->roomname : '');
        $panel->setAttribute("trackname", isset($s->trackname) ? $s->trackname : '');
        $panel->setAttribute("hashtag", isset($s->hashtag) ? $s->hashtag : '');
        $panel->setAttribute("starttime", isset($s->starttime) ? $s->starttime : '');
        
        foreach ($s->participants as $p) {
            $particpant = $xml -> createElement("participant");
            $particpant->setAttribute("pubsname", isset($p->pubsname) ? $p->pubsname : '');
            $particpant->setAttribute("badgeid", isset($p->badgeid) ? $p->badgeid : '');
            $particpant->setAttribute("moderator", isset($p->moderator) ? $p->moderator : '');
            $particpant->setAttribute("pronouns", isset($p->pronouns) ? $p->pronouns : '');

            $panel -> appendChild($particpant);

            if (!is_null($p->nextSession)) {
                $next = $xml -> createElement("nextSession");
                $next->setAttribute("title", isset($p->title) ? $p->nextSession->title : '');
                $next->setAttribute("progguiddesc", isset($p->progguiddesc) ? $p->nextSession->progguiddesc : '');
                $next->setAttribute("roomname", isset($p->roomname) ? $p->nextSession->roomname : '');
                $next->setAttribute("trackname", isset($p->trackname) ? $p->nextSession->trackname : '');
                $next->setAttribute("starttime", isset($p->starttime) ? $p->nextSession->starttime : '');
                $particpant -> appendChild($next);
            }
        }

        $doc -> appendChild($panel);
    }
    return $xml;
}

function get_scheduled_events_with_participants_as_xml() {
    return render_sessions_as_xml(get_scheduled_events_with_participants());
}

function get_scheduled_events_with_participants() {
    $ConStartDatim = CON_START_DATIM;
    $query1 =<<<EOD
SELECT
        S.sessionid, S.title, S.progguiddesc, R.roomname, SCH.roomid, PS.pubstatusname,
        DATE_FORMAT(ADDTIME('$ConStartDatim',SCH.starttime),'%a %l:%i %p') AS starttime,
        ADDTIME('$ConStartDatim',SCH.starttime) as starttimeraw,
        DATE_FORMAT(S.duration,'%i') AS durationmin, DATE_FORMAT(S.duration,'%k') AS durationhrs,
        T.trackname, KC.kidscatname, S.hashtag
    FROM
             Sessions S
        JOIN Schedule SCH USING (sessionid)
        JOIN Rooms R USING (roomid)
        JOIN PubStatuses PS USING (pubstatusid)
        JOIN Tracks T USING (trackid)
        JOIN KidsCategories KC USING (kidscatid)
    ORDER BY
        SCH.starttime, R.roomname;
EOD;

    $sessions = array();
    $sessionsById = array();
    $result = mysqli_query_with_error_handling($query1);

    while ($row = mysqli_fetch_assoc($result)) {
        $session = new ScheduledSession();
        $session->title = $row["title"];
        $session->sessionid = $row["sessionid"];
        $session->progguiddesc = $row["progguiddesc"];
        $session->roomname = $row["roomname"];
        $session->trackname = $row["trackname"];
        $session->hashtag = $row["hashtag"];
        $session->starttime = $row["starttime"];
        $session->starttime_unformatted = DateTime::createFromFormat( "Y-m-d H:i:s", $row["starttimeraw"] );
        $session->participants = array();


        $sessions[] = $session;
        $sessionsById[$session->sessionid] = $session;
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

    $result = mysqli_query_with_error_handling($query2);
    while ($row = mysqli_fetch_assoc($result)) {
        $session = $sessionsById[$row["sessionid"]];
        $participant = new ScheduledParticipant();
        $participant->pubsname = array_key_exists('pubsname', $row) ? $row["pubsname"] : '';
        $participant->moderator = array_key_exists('moderator', $row) ? $row["moderator"] : '';
        $participant->badgeid = array_key_exists('badgeid', $row) ? $row["badgeid"] : '';
        $participant->pronouns = array_key_exists('pronouns', $row) ? $row["pronouns"] : '';

        $session->participants[] = $participant;
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

?>