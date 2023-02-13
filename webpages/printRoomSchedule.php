<?php
    global $participant, $message_error, $message2, $congoinfo, $title, $linki;
    $title = "Room Schedule";
    require_once('StaffCommonCode.php');
    require_once('schedule_functions.php');

function render_rooms_as_xml($sessions) {
    $xml = new DomDocument("1.0", "UTF-8");
    $doc = $xml -> createElement("doc");
    $doc = $xml -> appendChild($doc);

    $rooms = array();

    foreach ($sessions as $s) {
        $room = $s->roomname;
        $day = date_format($s->starttime_unformatted, "l");

        if (!array_key_exists($room, $rooms)) {
            $rooms[$room] = array();
        }

        $roomDays = $rooms[$room];
        if (!array_key_exists($day, $roomDays)) {
            $roomDays[$day] = array($s);
        } else {
            $temp = $roomDays[$day];
            $temp[] = $s;
            $roomDays[$day] = $temp;
        }
        $rooms[$room] = $roomDays;
    }

    foreach ($rooms as $name => $dayArray) {
        $room = $xml->createElement("room");
        $room->setAttribute("name", $name);

        foreach ($dayArray as $day => $sessionList) {
            $dayXml = $xml->createElement("day");
            $dayXml->setAttribute("name", $day);

            foreach ($sessionList as $s) {
                $sessionXml = $xml->createElement("session");
                $sessionXml->setAttribute("title", $s->title);
                $sessionXml->setAttribute("startTime", date_format($s->starttime_unformatted, DISPLAY_24_HOUR_TIME ? "H:i" : "h:i"));
                $sessionXml->setAttribute("endTime", date_format($s->endtime_unformatted, DISPLAY_24_HOUR_TIME ? "H:i" : "h:i a"));

                $dayXml->appendChild($sessionXml);
            }

            $room -> appendChild($dayXml);
        }

        $doc -> appendChild($room);
    }
    return $xml;
}

$sessions = ScheduledSession::findAllScheduledSessionsWithParticipants($linki);

$paramArray = array();
if (defined('CON_THEME') && CON_THEME !== "") {
    $paramArray['additionalCss'] = CON_THEME;
}
if (array_key_exists("paper", $_REQUEST)) {
    $paper = $_REQUEST["paper"];
    $paramArray['paper'] = mb_strtolower($paper, "utf-8");
}

RenderXSLT('printRoomSchedule.xsl', $paramArray, render_rooms_as_xml($sessions));
?>
