<?php
// Copyright (c) 2015-2019 Peter Olszowka. All rights reserved. See copyright document for more details.

require_once('db_functions.php');

function retrieveKonOpasData($showpubstatus = 2, $showbio = 1) {
    $results = array();
    if (prepare_db_and_more() === false) {
        $results["message_error"] = "Unable to connect to database.<br />No further execution possible.";
        return $results;
    };

    $ConStartDatim = CON_START_DATIM;


    // first query: which people are on which sessions
    $query = <<<EOD
SELECT
    SCH.sessionid,
    P.badgeid,
    P.pubsname,
    P.sortedpubsname,
    POS.moderator
FROM
         Schedule SCH
    JOIN Sessions S USING (sessionid)
    JOIN ParticipantOnSession POS USING (sessionid)
    JOIN Participants P USING (badgeid)
WHERE
    S.pubstatusid IN ($showpubstatus) /* Public */
ORDER BY
    SCH.sessionid,
    POS.moderator DESC,
    P.badgeid;
EOD;
    $result = mysqli_query_with_error_handling($query);

    $sessionHasParticipant = array();
    $participantOnSession = array();
    while($row = mysqli_fetch_assoc($result)) {
        $sessionHasParticipant[$row["sessionid"]][] = array("id" => $row["badgeid"], "name" => $row["pubsname"].($row["moderator"] == "1" ? " (moderator)" : ""));
        $participantOnSession[$row["badgeid"]][] = $row["sessionid"];
    }


// query: active session information
    $query = <<<EOD
SELECT
    S.sessionid AS id,
    S.title,
    TR.trackname,
    TY.typename,
    R.roomname AS loc,
    R.floor,
    D.divisionname,
    DATE_FORMAT(duration, '%k') * 60 + DATE_FORMAT(duration, '%i') AS mins,
    S.progguiddesc AS `desc`,
    S.progguidhtml AS `deschtml`,
    DATE_FORMAT(ADDTIME('$ConStartDatim',SCH.starttime),'%Y-%m-%d') as date,
    DATE_FORMAT(ADDTIME('$ConStartDatim',SCH.starttime),'%H:%i') as time,
    GROUP_CONCAT(TA.tagname SEPARATOR ',') AS taglist,
    S.meetinglink
FROM
              Schedule SCH
         JOIN Sessions S USING (sessionid)
         JOIN Tracks TR USING (trackid)
         JOIN Types TY USING (typeid)
         JOIN Rooms R USING (roomid)
         JOIN Divisions D ON (S.divisionid = D.divisionid)
    LEFT JOIN SessionHasTag SHT USING (sessionid)
    LEFT JOIN Tags TA USING (tagid)
WHERE
    S.pubstatusid IN ($showpubstatus) /* Public */
GROUP BY
    S.sessionid
ORDER BY
    S.sessionid;
EOD;
    $result = mysqli_query_with_error_handling($query);

    $results["program_num_rows"] = mysqli_num_rows($result);        //used for reporting
    $program = array();
    while($row = mysqli_fetch_assoc($result)) {
        $tagsArray = array("Track:".$row["trackname"], "Division:".$row["divisionname"]);
        if (!empty($row['typename'])) {
            $tagsArray[] = 'Type:'.$row['typename'];
        }
        if (!empty($row["taglist"])) {
            $temparrayoftags = explode(',', $row["taglist"]);
            foreach ($temparrayoftags as $singletag) {
                array_push($tagsArray, "Tag:".$singletag);
            }
        }
        $locfloor = '';
        if ($row["floor"] && $row["floor"] != "") {
            $locfloor = ' - ' . $row["floor"];
        }
        $desc = $row["desc"];
        if (!empty($row["deschtml"])) {
            $desc = $row["deschtml"];
        }
        $programRow = array(
            "id"     => $row["id"],
            "title"  => $row["title"],
            "tags"   => $tagsArray,
            "date"   => $row["date"],
            "time"   => $row["time"],
            "mins"   => $row["mins"],
            "loc"    => array($row["loc"] . $locfloor),
            "people" => $sessionHasParticipant[$row["id"]],
            "desc"   => $desc,
            "links"  => []
            );
            if (!empty($row["meetinglink"])) {
                $programRow["links"] = ["meeting" => $row["meetinglink"]];
            }
            $program[] = $programRow;
    }


// query: active participant information
    $query = <<<EOD
SELECT
    P.badgeid,
    P.pubsname,
    P.sortedpubsname,
    P.bio,
    P.htmlbio,
    CD.firstname,
    CD.lastname
FROM
         Participants P
    JOIN CongoDump CD USING (badgeid)
WHERE
    P.badgeid IN (
        SELECT POS.badgeid
        FROM
                 ParticipantOnSession POS
            JOIN Sessions S USING (sessionid)
            JOIN Schedule SCH USING (sessionid)
        WHERE S.pubstatusid IN ($showpubstatus) /* Public */
        )
EOD;
    $result = mysqli_query_with_error_handling($query);

    $results["people_num_rows"] = mysqli_num_rows($result);     //used for reporting
    $people = array();
    while($row = mysqli_fetch_assoc($result)) {
        if (empty($row["pubsname"])) {
            $name = $row["lastname"] . ', ' . $row["firstname"];
        } else {
            $name = $row["pubsname"];
        }
        if ($showbio==0) {
            $row["bio"] = '';
            $row["htmlbio"] = '';
        }
        $bio = $row["bio"];
        if (!empty($row["htmlbio"])) {
            $bio = $row["htmlbio"];
        }
        $peopleRow = array(
            "id" => $row["badgeid"],
            "name" => array($name),
            "sortname" => $row["sortedpubsname"],
            "prog" => $participantOnSession[$row["badgeid"]],
            "bio" => $bio
            );
        $people[] = $peopleRow;
    }

    //note:header('Content-type: application/json');

    //The json key is for general json output
    $results["json"]  = "var program = " . json_encode($program).";\n";
    $results["json"] .= "var people = " . json_encode($people).";\n";

    //The program, people and konopas keys are for KonOpas
    $results["program"]  = "var program = " . json_encode($program).";\n";
    $results["people"]   = "var people = " . json_encode($people).";\n";
    $results["konopas"]  = "CACHE MANIFEST\n";
    $results["konopas"] .= "# " . date("Y-m-d H:i:s") . "\n";
    $results["konopas"] .= "\n";
    $results["konopas"] .= "CACHE:\n";
    $results["konopas"] .= "cap/program.js\n";
    $results["konopas"] .= "cap/people.js\n";
    $results["konopas"] .= "cap/title.png\n";
    $results["konopas"] .= "konopas.min.js\n";
    $results["konopas"] .= "skin/konopas.css\n";
    $results["konopas"] .= "skin/icons.png\n";
    $results["konopas"] .= "skin/Roboto300.ttf\n";
    $results["konopas"] .= "skin/Roboto500.ttf\n";
    $results["konopas"] .= "skin/Oswald400.ttf\n";
    $results["konopas"] .= "favicon.ico\n";
    $results["konopas"] .= "\n";
    $results["konopas"] .= "NETWORK:\n";
    $results["konopas"] .= "*\n";

    return $results;

}


function retrieveInfoData() {
    $infofile = array();
    if (prepare_db_and_more() === false) {
        $infofile["message_error"] = "Unable to connect to database.<br />No further execution possible.";
        return $infofile;
    };

    $ConStartDatim = CON_START_DATIM;
    $CON_NAME = CON_NAME;
    $CON_URL = CON_URL;

    // query to get locations data
    $query = <<<EOD
SELECT
    L.locationname,
    L.roomname,
    L.locationhours
FROM
    Locations L
ORDER BY display_order
EOD;
    $result = mysqli_query_with_error_handling($query);

    // Need to loop through locations and replace the html formatting with markdown tags
    // Assumes that the only html used is <br /> and <u></u> and <em></em>
    $locations = array();
    $locations["output"] = "# Department Hours and Locations" . "\n\n";
    while($row = mysqli_fetch_assoc($result)) {
        $locations["output"] .= "## " . $row["locationname"] . " - " . $row["roomname"] . "\n\n";
        $lochoursstr = $row["locationhours"];
        $lochoursstr = str_replace("<br />", "  ", $lochoursstr);
        $lochoursstr = str_replace(array("<em>", "</em>"), array("*", "*"), $lochoursstr);
        $lochoursstr = str_replace(array("<u>", "</u>"), array("**", "**"), $lochoursstr);
        $lochoursstr = str_replace(array("<strong>", "</strong>"), array("***", "***"), $lochoursstr);
        
        $locations["output"] .= $lochoursstr . "\n";
    }


    $infofile["output"]  = "\n";
    $infofile["output"] .= "Program and participant data were last updated " . date("F j, Y, g:i a T") . "\n\n";
    $infofile["output"] .= "---\n";

    $infofile["output"] .= "# Information" . "\n\n";
    $infofile["output"] .= "Use markdown to enter information here." . "\n\n";
    $infofile["output"] .= "All times listed are in CST unless otherwise noted." . "\n\n";
    $infofile["output"] .= "---\n";

    $infofile["output"] .= "# Links" . "\n\n";
    //$infofile["output"] .= "[Example Link](https://chicon.org/)" . "\n\n";
    //$infofile["output"] .= "[Example link to image](image.jpg)" . "\n\n";
    $infofile["output"] .= "---\n";

    $infofile["output"] .= $locations["output"];
    $infofile["output"] .= "---\n";

    $infofile["output"] .= "# About Conclár" . "\n\n";
    $infofile["output"] .= "Conclár is a browser based program guide used by [" . $CON_NAME . "](" . $CON_URL . ")." . "\n\n";

    return $infofile;
}
?>