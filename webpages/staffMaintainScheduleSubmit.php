<?php
// Copyright (c) 2011-2020 Peter Olszowka. All rights reserved. See copyright document for more details.

require_once('StaffCommonCode.php');
require_once('SubmitMaintainRoom.php');

function retrieveRoomsTable() {
    global $message_error, $unitsPerBlock, $standardRowHeight, $title;
    $foo = "";
    if (STANDARD_BLOCK_LENGTH == "1:30") {
        $unitsPerBlock = 3;
        $standardRowHeight = 17;
    } elseif (STANDARD_BLOCK_LENGTH == "1:00") {
        $unitsPerBlock = 2;
        $standardRowHeight = 22;
    } else {
        RenderErrorAjax("Constant(configuration parameter) STANDARD_BLOCK_LENGTH undefined or defined incorrectly.\n");
        exit();
    }
    if (empty($_POST["roomsToDisplayArray"])) {
        exit();
    }
    $roomsToDisplayArray = $_POST["roomsToDisplayArray"];
    $roomsToDisplayList = implode(",", $roomsToDisplayArray);
    $queryArray["rooms"] = <<<EOD
SELECT
        R.roomid,
        R.roomname
    FROM
        Rooms R
    WHERE
        R.roomid IN ($roomsToDisplayList)
    ORDER BY
        R.display_order
EOD;
    if (($resultXML = mysql_query_XML($queryArray)) === false) {
        RenderError($message_error, true);
        exit();
    }
    $xmlstr = <<<EOD
<?xml version="1.0" encoding="UTF-8"?>
    <xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" >
        <xsl:output omit-xml-declaration="yes" />
        <xsl:template match="/">
            <xsl:apply-templates match="doc/query[@queryName='rooms']/row" />
        </xsl:template>
        <xsl:template match="/doc/query[@queryName='rooms']/row">
            <th class="schedulerGridRoom" roomid="{@roomid}"><xsl:value-of select="@roomname" /></th>
        </xsl:template>
    </xsl:stylesheet>
EOD;
    $xsl = new DomDocument;
    $xsl->loadXML($xmlstr);
    $xslt = new XsltProcessor();
    $xslt->importStylesheet($xsl);
    $roomsHtml = $xslt->transformToXML($resultXML);
    $roomnameArray = array();
    $query = <<<EOD
SELECT
        R.roomid,
        R.roomname
    FROM
        Rooms R
    WHERE
        R.roomid IN ($roomsToDisplayList);
EOD;
    $result = mysqli_query_with_error_handling($query);
    while ($row = mysqli_fetch_assoc($result)) {
        $roomnameArray[$row["roomid"]] = $row["roomname"];
    }
    //echo '<pre>'; print_r($roomnameArray); echo '</pre>';
    mysqli_free_result($result);
    $htmlTimesArray = getScheduleTimesArray($roomsToDisplayList);
    $query = <<<EOD
SELECT
        SCH.scheduleid,
        SCH.roomid,
        SCH.starttime,
        ADDTIME(SCH.starttime, S.duration) AS endtime,
        S.sessionid,
        S.title,
        S.progguiddesc,
        TR.trackname,
        TY.typename,
        D.divisionname,
        S.duration,
        R.roomname
    FROM
             Schedule SCH
        JOIN Sessions S USING (sessionid)
        JOIN Tracks TR USING (trackid)
        JOIN Types TY USING (typeid)
        JOIN Divisions D ON (D.divisionid = S.divisionid)
        JOIN Rooms R USING (roomid)
    WHERE
        SCH.roomid IN ($roomsToDisplayList)
    ORDER BY
        SCH.roomid, SCH.starttime, endtime DESC;
EOD;
    $result = mysqli_query_with_error_handling($query);
    $scheduleArray = array();
    foreach ($roomsToDisplayArray as $roomIndex => $roomId) {
        $scheduleArray[$roomId] = array();
    }
    while ($row = mysqli_fetch_assoc($result)) {
        list($startTimeHour, $startTimeMin, $foo) = sscanf($row["starttime"], "%d:%d:%d");
        list($endTimeHour, $endTimeMin, $foo) = sscanf($row["endtime"], "%d:%d:%d");
        $scheduleArray[$row["roomid"]][] = array(
            "scheduleid" => $row["scheduleid"],
            "starttime" => $row["starttime"],
            "endtime" => $row["endtime"],
            "roomname" => $row["roomname"],
            "startTimeUnits" => convertStartTimeToUnits($startTimeHour, $startTimeMin),
            "endTimeUnits" => convertEndTimeToUnits($endTimeHour, $endTimeMin),
            "sessionid" => $row["sessionid"],
            "title" => $row["title"],
            "progguiddesc" => $row["progguiddesc"],
            "trackname" => $row["trackname"],
            "typename" => $row["typename"],
            "divisionname" => $row["divisionname"],
            "duration" => $row["duration"]);
    }
    $roomsHTMLArray = array();
    foreach ($roomsToDisplayArray as $roomIndex => $roomId) {
        $roomsHTMLArray[$roomIndex] = getHTMLforRoom($roomId, $htmlTimesArray, $scheduleArray, $roomnameArray);
    }
    echo "<table class=\"schedulerGrid\">\n";
    echo "<tr>\n";
    echo $htmlTimesArray[0]["html"];
    echo $roomsHtml;
    echo "</tr>\n";
    for ($i = 1; $i < count($htmlTimesArray); $i++) {
        echo "<tr>\n";
        echo $htmlTimesArray[$i]["html"];
        foreach ($roomsToDisplayArray as $roomIndex => $roomId) {
            if (isset($roomsHTMLArray[$roomIndex][$i])) {
                echo $roomsHTMLArray[$roomIndex][$i];
            }
        }
        echo "</tr>\n";
    }
    echo "</table>\n";
}

function getScheduleTimesArray($roomsToDisplayList) {
    global $message_error, $unitsPerBlock, $standardRowHeight;
    $htmlTimesArray = array();
    $nextStartTimeUnits = 0;
    list($firstDayStartTimeHour, $firstDayStartTimeMin) = sscanf(FIRST_DAY_START_TIME, "%d:%d");
    $firstDayStartTimeUnits = convertStartTimeToUnits($firstDayStartTimeHour, $firstDayStartTimeMin);
    list($otherDayEndTimeHour, $otherDayEndTimeMin) = sscanf(OTHER_DAY_STOP_TIME, "%d:%d");
    $otherDayEndTimeUnits = convertStartTimeToUnits($otherDayEndTimeHour, $otherDayEndTimeMin);
    list($otherDayStartTimeHour, $otherDayStartTimeMin) = sscanf(OTHER_DAY_START_TIME, "%d:%d");
    $otherDayStartTimeUnits = convertStartTimeToUnits($otherDayStartTimeHour, $otherDayStartTimeMin);
    list($lastDayEndTimeHour, $lastDayEndTimeMin) = sscanf(LAST_DAY_STOP_TIME, "%d:%d");
    $lastDayEndTimeUnits = convertStartTimeToUnits($lastDayEndTimeHour, $lastDayEndTimeMin);
    $cutoffHour = DAY_CUTOFF_HOUR;
    $cutoffUnits = convertStartTimeToUnits($cutoffHour, 0);
    $nextStartTimeHour = 0;
    $nextStartTimeMin = 0;
    $gap = false;
    for ($day = 1; $day <= CON_NUM_DAYS; $day++) {
        $dayName = longDayNameFromInt($day);
        if ($day < CON_NUM_DAYS) {
            $nextDayName = longDayNameFromInt($day + 1);
        }
        $thisCutoffUnits = $cutoffUnits + $day * 48; // always next day even on 1st day
        $thisCutoffTimeStr = "MAKETIME(" . floor($thisCutoffUnits / 2) . ",0,0)";
        if ($day == 1) {
            //calculate beginning for first day
            // mode: -1 is gap; 0, 1, & 2 are rows of standard block
            $htmlTimesArray[] = array("hr" => 0, "min" => 0, "mode" => -1, "units" => 0, "html" => "<th>" . $dayName . "</th>");

            $query = "SELECT MIN(SCH.starttime) AS starttime FROM Schedule SCH WHERE roomid IN ($roomsToDisplayList);";
            $result = mysqli_query_with_error_handling($query, true, true);
            $row = mysqli_fetch_assoc($result);
            $scalarResult = $row["starttime"];
            if ($scalarResult === null) {
                $startTimeUnits = $firstDayStartTimeUnits;
            } else {
                list($startTimeHour, $startTimeMin, $foo) = sscanf($scalarResult, "%d:%d:%d");
                $startTimeUnits = convertStartTimeToUnits($startTimeHour, $startTimeMin);
                if ($startTimeHour === null || $firstDayStartTimeUnits < $startTimeUnits) {
                    $startTimeUnits = $firstDayStartTimeUnits;
                }
            }
        } else {
            // calculate beginning for other than first day
            $startTimeUnits = $nextStartTimeUnits;
            if ($gap) {
                $htmlTimesArray[] = array("hr" => 0, "min" => 0, "mode" => -1, "units" => 0, "html" => "<td class=\"gap\">" . $dayName . "</td>");
            }
        }
        // found beginning; now find ending
        if ($day < CON_NUM_DAYS) {
            $previousCutoffUnits = $cutoffUnits + ($day - 1) * 48;
            $previousCutoffTimeStr = "MAKETIME(" . floor($previousCutoffUnits / 2) . ",0,0)";
            $query = <<<EOD
SELECT
        MAX(ADDTIME(SCH.starttime, S.duration)) AS endtime
    FROM
             Schedule SCH
        JOIN Sessions S USING(sessionid)
    WHERE
        SCH.roomid IN ($roomsToDisplayList)
        AND ADDTIME(SCH.starttime, S.duration) < $thisCutoffTimeStr
        AND SCH.starttime >= $previousCutoffTimeStr;
EOD;
            $result = mysqli_query_with_error_handling($query);
            if (!$result) {
                RenderErrorAjax($message_error);
                exit();
            }
            $row = mysqli_fetch_assoc($result);
            $scalarResult = $row["endtime"];
            if ($scalarResult == null) {
                $foo = 1;
                $endTimeUnits = $otherDayEndTimeUnits + ($day - 1) * 48;
            } else {
                list($endTimeHour, $endTimeMin, $foo) = sscanf($scalarResult, "%d:%d:%d");
                $endTimeUnits = convertEndTimeToUnits($endTimeHour, $endTimeMin);
                if ($endTimeHour == null || $endTimeUnits < $otherDayEndTimeUnits + ($day - 1) * 48) {
                    $endTimeUnits = $otherDayEndTimeUnits + ($day - 1) * 48;
                }
            }
            if ($endTimeUnits >= $thisCutoffUnits) {
                $gap = false;
                $endTimeUnits = $thisCutoffUnits - 1;
                $nextStartTimeUnits = $thisCutoffUnits;
            } else {
                $query = <<<EOD
SELECT
        MIN(SCH.starttime) AS starttime
    FROM
             Schedule SCH
        JOIN Sessions S USING(sessionid)
    WHERE
        SCH.roomid IN ($roomsToDisplayList)
        AND ADDTIME(SCH.starttime, S.duration) >= $thisCutoffTimeStr;
EOD;
                $result = mysqli_query_with_error_handling($query, true, true);
                $row = mysqli_fetch_assoc($result);
                $scalarResult = $row["starttime"];
                if ($scalarResult === null) {
                    $gap = true;
                    $nextStartTimeUnits = $otherDayStartTimeUnits + $day * 48;
                } else {
                    list($nextStartTimeHour, $nextStartTimeMin) = sscanf($scalarResult, "%d:%d");
                    $nextStartTimeUnits = convertStartTimeToUnits($nextStartTimeHour, $nextStartTimeMin);
                    if ($nextStartTimeHour == null) {
                        $gap = true;
                        $nextStartTimeUnits = $otherDayStartTimeUnits + $day * 48;
                    } elseif (($nextStartTimeUnits - $endTimeUnits) >= 2) {
                        $gap = true;
                        if ($nextStartTimeUnits > $otherDayStartTimeUnits + $day * 48) {
                            $nextStartTimeUnits = $otherDayStartTimeUnits + $day * 48;
                        }
                    } else {
                        $gap = false;
                        $endTimeUnits = $thisCutoffUnits - 1;
                        $nextStartTimeUnits = $thisCutoffUnits;
                    }
                }
            }
        } else {
            //finding end for last day now
            $query = <<<EOD
SELECT
        MAX(ADDTIME(SCH.starttime, S.duration)) AS endtime
    FROM
             Schedule SCH
        JOIN Sessions S USING(sessionid)
    WHERE
        SCH.roomid IN ($roomsToDisplayList);
EOD;
            $result = mysqli_query_with_error_handling($query, true, true);
            $row = mysqli_fetch_assoc($result);
            $scalarResult = $row["endtime"];
            if ($scalarResult === null) {
                $endTimeUnits = $lastDayEndTimeUnits + ($day - 1) * 48;
            }
            else {
                list($endTimeHour, $endTimeMin, $foo) = sscanf($scalarResult, "%d:%d:%d");
                $endTimeUnits = convertEndTimeToUnits($endTimeHour, $endTimeMin);
                if ($endTimeHour == null || $endTimeUnits < $lastDayEndTimeUnits + ($day - 1) * 48) {
                    $endTimeUnits = $lastDayEndTimeUnits + ($day - 1) * 48;
                }
            }
        }
        $nowInUnits = $startTimeUnits;
        $nowHour = 0;
        $nowMin = 0;
        // mode cycles 0, 1, 2 for 3 units per block and 1, 2 for 2 units per block
        if ($day == 1) {
            $modeIndex = ($nowInUnits - $firstDayStartTimeUnits) % $unitsPerBlock;
        } else {
            $modeIndex = ($nowInUnits - $otherDayStartTimeUnits) % $unitsPerBlock;
        }
        if ($unitsPerBlock == 2) {
            $modeIndex++;
        }
        while ($modeIndex < (3 - $unitsPerBlock)) {
            $modeIndex += $unitsPerBlock;
        }
        while ($nowInUnits <= $endTimeUnits) {
            if ($modeIndex > 2) {
                $modeIndex = (3 - $unitsPerBlock);
            }
            list($nowHour, $nowMin) = convertUnitsToHourMin($nowInUnits);
            $htmlTimesArray[] = array("hr" => $nowHour, "min" => $nowMin, "units" => $nowInUnits, "mode" => $modeIndex);
            end($htmlTimesArray);
            $mykey = key($htmlTimesArray);
            if ($nowHour < ($day * 24)) {
                $titleStr = $dayName;
            } else {
                $titleStr = $dayName . " overnight into " . $nextDayName;
            }
            if ($nowMin == 0) {
                $class = "timeTop";
                if (DISPLAY_24_HOUR_TIME) {
                    $displayTime = ($nowHour % 24) . ':00';
                }
                else {
                    // ToDo: This could be a good candidate for PHP 8.0's new match() statement.
                    $displayTime =
                        (($nowHour % 24 == 0) ? '12:00a' :
                        (($nowHour % 24 < 12) ? ($nowHour % 24) . ':00a' :
                        (($nowHour % 24 == 12) ? '12:00p' :
                        (($nowHour % 24) - 12) . ':00p')));
                }
            } else {
                $class = "timeBottom";
                $displayTime = '&nbsp;';
            }
            $htmlTimesArray[$mykey]["html"] = "<td class=\"${class}\" style=\"height:{$standardRowHeight}px\" title=\"$titleStr\" mode=\"$modeIndex\">${displayTime}</td>";
            $nowInUnits++;
            $modeIndex++;
        }
    }
    return $htmlTimesArray;
}

function getHTMLforRoom($roomId, $htmlTimesArray, $scheduleArray, $roomnameArray) {
    global $thisRoomSchedArray, $key, $thisSlotEndUnits, $blockHTML, $thisSlotLength, $thisSlotBeginUnits, $standardRowHeight;
    $roomHTMLColumn = array();
    $schedLength = count($htmlTimesArray);
    $thisRoomSchedArray = $scheduleArray[$roomId];
    reset($thisRoomSchedArray);
    $key = key($thisRoomSchedArray);
    $i = 1;
    do {    //length counted from 0, but we're skipping 1st one (numbered 0)
        if ($htmlTimesArray[$i]["mode"] == -1) {    // gap
            $roomHTMLColumn[$i] = "<td class=\"gap schedulerGridRoom\">&nbsp;</td>";
            $i++;
            continue;
        }
        $thisSlotBeginUnits = $htmlTimesArray[$i]["units"];
        // mode cycles 0, 1, 2 for 3 units per block and 1, 2 for 2 units per block
        switch ($htmlTimesArray[$i]["mode"]) {
            case "0":
                if (!isset($htmlTimesArray[$i + 1]["mode"]) || $htmlTimesArray[$i + 1]["mode"] == -1) {
                    $thisSlotEndUnits = $thisSlotBeginUnits + 1;
                    $thisSlotLength = 1;
                } elseif (!isset($htmlTimesArray[$i + 2]["mode"]) || $htmlTimesArray[$i + 2]["mode"] == -1) {
                    $thisSlotEndUnits = $thisSlotBeginUnits + 2;
                    $thisSlotLength = 2;
                } else {
                    $thisSlotEndUnits = $thisSlotBeginUnits + 3;
                    $thisSlotLength = 3;
                }
                break;
            case "1":
                if (!isset($htmlTimesArray[$i + 1]["mode"]) || $htmlTimesArray[$i + 1]["mode"] == -1) {
                    $thisSlotEndUnits = $thisSlotBeginUnits + 1;
                    $thisSlotLength = 1;
                } else {
                    $thisSlotEndUnits = $thisSlotBeginUnits + 2;
                    $thisSlotLength = 2;
                }
                break;
            case "2":
                $thisSlotEndUnits = $thisSlotBeginUnits + 1;
                $thisSlotLength = 1;
                break;
        }
        // determined slot
        doABlock($roomId, $roomnameArray[$roomId]);
        $roomHTMLColumn[$i] = $blockHTML;
        $i += $thisSlotLength;
    } while ($i < $schedLength);
    return $roomHTMLColumn;
}

function doABlock($roomId, $roomname) {
    global $thisRoomSchedArray, $key, $thisSlotEndUnits, $blockHTML, $thisSlotLength, $thisSlotBeginUnits, $thisSlot, $standardRowHeight;
    if (!isset($thisRoomSchedArray[$key]) || $thisRoomSchedArray[$key]["startTimeUnits"] >= $thisSlotEndUnits) {
        // room is empty
        $blockHTML = emptySchedBlock($roomId, $roomname);
        return;
    }
    if ($thisRoomSchedArray[$key]["startTimeUnits"] > $thisSlotBeginUnits) {
        // make empty slot before session start
        $thisSlotEndUnits = $thisRoomSchedArray[$key]["startTimeUnits"];
        $thisSlotLength = $thisSlotEndUnits - $thisSlotBeginUnits;
        $blockHTML = emptySchedBlock($roomId, $roomname);
        return;
    }
    if ($thisRoomSchedArray[$key]["endTimeUnits"] != $thisSlotEndUnits) {
        // need to modify the slot -- shrink or stretch
        $thisSlotEndUnits = $thisRoomSchedArray[$key]["endTimeUnits"];
        $thisSlotLength = $thisSlotEndUnits - $thisSlotBeginUnits;
    }
    if (!isset($thisRoomSchedArray[$key + 1]) || $thisRoomSchedArray[$key + 1]["startTimeUnits"] >= $thisSlotEndUnits) {
        // only one item in the slot
        // render a simple block with one session
        $blockHTML = "<td class=\"schedulerGridRoom schedulerGridSlot\"";
        if ($thisSlotLength > 1) {
            $blockHTML .= " rowspan=\"$thisSlotLength\"";
        }
        $blockHTML .= ">";
        $blockHTML .= "<div class=\"schedulerGridContainer\" style=\"height:" . ($thisSlotLength * $standardRowHeight - 2) . "px;\">";
        $blockHTML .= "<div id=\"sessionBlockDIV_{$thisRoomSchedArray[$key]["sessionid"]}\" class=\"scheduledSessionBlock\" ";
        $blockHTML .= "sessionid=\"{$thisRoomSchedArray[$key]["sessionid"]}\" scheduleid=\"{$thisRoomSchedArray[$key]["scheduleid"]}\" ";
        $blockHTML .= "roomid=\"$roomId\" startTimeUnits=\"$thisSlotBeginUnits\" endTimeUnits=\"$thisSlotEndUnits\" ";
        $blockHTML .= "startTime=\"{$thisRoomSchedArray[$key]["starttime"]}\" endTime=\"{$thisRoomSchedArray[$key]["endtime"]}\" ";
        $blockHTML .= "duration=\"{$thisRoomSchedArray[$key]["duration"]}\" roomname=\"{$thisRoomSchedArray[$key]["roomname"]}\" >";
        $blockHTML .= "<div class=\"sessionBlockTitleRow\">";
        $blockHTML .= "<i class=\"icon-info-sign getSessionInfoP\"></i>";
        //$blockHTML .= "<div class=\"ui-icon ui-icon-info getSessionInfoP\"></div>";
        $blockHTML .= "<div class=\"sessionBlockTitle\">{$thisRoomSchedArray[$key]["title"]}</div>";
        $blockHTML .= "</div>";
        $blockHTML .= "<div>";
        $blockHTML .= "<span class=\"sessionBlockId\">{$thisRoomSchedArray[$key]["sessionid"]}</span>";
        $blockHTML .= "<span class=\"sessionBlockDivis\">{$thisRoomSchedArray[$key]["divisionname"]}</span>";
        $blockHTML .= "</div>";
        $blockHTML .= "<div>";
        $blockHTML .= "<span class=\"sessionBlockType\">{$thisRoomSchedArray[$key]["typename"]}</span>";
        $blockHTML .= "<span class=\"sessionBlockTrack\">{$thisRoomSchedArray[$key]["trackname"]}</span>";
        $blockHTML .= "</div>"; // last row of info
        $blockHTML .= "</div>"; // session block
        $blockHTML .= "</div>"; // container
        $blockHTML .= "</td>";
        $key++;
        return;
    }
    // need to find all the sessions in the collection before a time border across which none extend
    $i = 1;
    while (isset($thisRoomSchedArray[$key + $i])) {
        if ($thisRoomSchedArray[$key + $i]["startTimeUnits"] >= $thisSlotEndUnits) {
            // found a session outside the "cluster"
            $i--;
            break;
        }
        if ($thisRoomSchedArray[$key + $i]["endTimeUnits"] >= $thisSlotEndUnits) {
            $thisSlotEndUnits = $thisRoomSchedArray[$key + $i]["endTimeUnits"];
            $thisSlotLength = $thisSlotEndUnits - $thisSlotBeginUnits;
        }
        $i++;
    }
    if (!isset($thisRoomSchedArray[$key + $i])) {
        //for now it is important to point to last existing key
        $i--;
    }
    // having found the cluster, need to determine whether any slots have more than two sessions occupying them.
    $slotCounter = array();
    for ($thisSlot = $thisSlotBeginUnits; $thisSlot < $thisSlotEndUnits; $thisSlot++) {
        $slotCounter[$thisSlot] = 0;
        for ($thisKey = $key; $thisKey <= $key + $i; $thisKey++) {
            if ($thisSlot >= $thisRoomSchedArray[$thisKey]["startTimeUnits"] &&
                $thisSlot < $thisRoomSchedArray[$thisKey]["endTimeUnits"]) {
                $slotCounter[$thisSlot]++;
                if ($slotCounter[$thisSlot] > 2) {
                    renderComplicatedBlock($roomId);
                    $key += $i + 1;
                    return;
                }
            }
        }
    }
    // having found the cluster, need to render it and reset the key
    $blockHTML = "<td class=\"schedulerGridRoom schedulerGridSlot\"";
    if ($thisSlotLength > 1) {
        $blockHTML .= " rowspan=\"$thisSlotLength\"";
    }
    $blockHTML .= ">";
    $blockHTML .= "<div class=\"scheduleGridCompoundDIV\" style=\"height:" . floor($thisSlotLength * ($standardRowHeight + 2)) . "px;\" roomid=\"$roomId\" ";
    $blockHTML .= "startTimeUnits=\"$thisSlotBeginUnits\" endTimeUnits=\"$thisSlotEndUnits\">";
    $blockHTML .= "<table class=\"scheduleGridCompTAB\">";
    $AScheduledUpTo = $thisSlotBeginUnits;
    $BScheduledUpTo = $thisSlotBeginUnits;
    $thisKey = $key;
    for ($thisSlot = $thisSlotBeginUnits; $thisSlot < $thisSlotEndUnits; $thisSlot++) {
        $blockHTML .= "<tr class=\"compoundTR\" style=\"height:" . ($standardRowHeight + 2) . "px\" >";
        doACompSlot($AScheduledUpTo, $thisSlot, $thisKey, $i, $roomId);
        doACompSlot($BScheduledUpTo, $thisSlot, $thisKey, $i, $roomId);
        $blockHTML .= "</tr>";
    }
    $blockHTML .= "</table>";
    $blockHTML .= "</div></td>";
    $key += $i + 1;
}

function doACompSlot(&$ScheduledUpTo, $thisSlot, &$thisKey, $i, $roomId) {
    global $key, $thisSlotBeginUnits, $thisSlotEndUnits, $blockHTML, $thisRoomSchedArray, $standardRowHeight;
    if ($ScheduledUpTo == $thisSlot) {
        if ($thisKey > $key + $i) {
            // no more sessions to put into the compound block; just put in blanks through the end
            $thisCBlockLength = $thisSlotEndUnits - $thisSlot;
            $blockHTML .= "<td";
            if ($thisCBlockLength > 1) {
                $blockHTML .= " rowspan=\"$thisCBlockLength\"";
            }
            $blockHTML .= " class=\"compoundTD\">";
            $blockHTML .= "<div class=\"scheduleGridCompoundEmptyDIV\" style=\"height:" . ($thisCBlockLength * $standardRowHeight) . "px\" ";
            $blockHTML .= " roomid=\"$roomId\" startTimeUnits=\"$thisSlot\" roomname=\"{$thisRoomSchedArray[$thisKey]["roomname"]}\">&nbsp;</div>";
            $blockHTML .= "</td>";
            $ScheduledUpTo = $thisSlotEndUnits;
            // can assume we are not done with blocks at this point
        } else if ($thisRoomSchedArray[$thisKey]["startTimeUnits"] == $thisSlot) {
            // put in a real session
            $thisCBlockLength = $thisRoomSchedArray[$thisKey]["endTimeUnits"] - $thisRoomSchedArray[$thisKey]["startTimeUnits"];
            $ScheduledUpTo = $thisRoomSchedArray[$thisKey]["endTimeUnits"];
            $blockHTML .= "<td";
            if ($thisCBlockLength > 1) {
                $blockHTML .= " rowspan=\"$thisCBlockLength\"";
            }
            $blockHTML .= " class=\"compoundTD\" style=\"height:" . ($thisCBlockLength * ($standardRowHeight + 2)) . "px\" >";
            $blockHTML .= "<div class=\"scheduleGridCompoundSessContainer\" style=\"height:" . ($thisCBlockLength * ($standardRowHeight + 2) - 2) . "px\" >";
            $blockHTML .= "<div id=\"sessionBlockDIV_{$thisRoomSchedArray[$thisKey]["sessionid"]}\" class=\"scheduledSessionBlock\" ";
            $blockHTML .= "sessionid=\"{$thisRoomSchedArray[$thisKey]["sessionid"]}\" ";
            $blockHTML .= "scheduleid=\"{$thisRoomSchedArray[$thisKey]["scheduleid"]}\" ";
            $blockHTML .= "roomid=\"$roomId\" startTimeUnits=\"{$thisRoomSchedArray[$thisKey]["startTimeUnits"]}\" ";
            $blockHTML .= "endTimeUnits=\"{$thisRoomSchedArray[$thisKey]["endTimeUnits"]}\" ";
            $blockHTML .= "startTime=\"{$thisRoomSchedArray[$thisKey]["starttime"]}\" roomname=\"{$thisRoomSchedArray[$thisKey]["roomname"]}\" ";
            $blockHTML .= "endTime=\"{$thisRoomSchedArray[$thisKey]["endtime"]}\" duration=\"{$thisRoomSchedArray[$thisKey]["duration"]}\" >";
            $blockHTML .= "<div class=\"sessionBlockTitleRow\">";
            $blockHTML .= "<i class=\"icon-info-sign getSessionInfoP\"></i>";
            //$blockHTML .= "<div class=\"ui-icon ui-icon-info getSessionInfoP\"></div>";
            $blockHTML .= "<div class=\"sessionBlockTitle\">{$thisRoomSchedArray[$thisKey]["title"]}</div>";
            $blockHTML .= "</div>";
            $blockHTML .= "<div>";
            $blockHTML .= "<span class=\"sessionBlockId\">{$thisRoomSchedArray[$thisKey]["sessionid"]}</span>";
            $blockHTML .= "<span class=\"sessionBlockDivis\">{$thisRoomSchedArray[$thisKey]["divisionname"]}</span>";
            $blockHTML .= "</div>";
            $blockHTML .= "<div>";
            $blockHTML .= "<span class=\"sessionBlockType\">{$thisRoomSchedArray[$thisKey]["typename"]}</span>";
            $blockHTML .= "<span class=\"sessionBlockTrack\">{$thisRoomSchedArray[$thisKey]["trackname"]}</span>";
            $blockHTML .= "</div>"; // last row of info
            $blockHTML .= "</div>"; // session block
            $blockHTML .= "</div>"; // container
            $blockHTML .= "</td>";
            $thisKey++;
        } else {
            // put in a blank spot up to the next session
            // don't have to worry about "reserving" the session, the next
            // one will necessarily go here
            $thisCBlockLength = $thisRoomSchedArray[$thisKey]["startTimeUnits"] - $thisSlot;
            $blockHTML .= "<td";
            if ($thisCBlockLength > 1) {
                $blockHTML .= " rowspan=\"$thisCBlockLength\"";
            }
            $blockHTML .= " class=\"compoundTD\">";
            $blockHTML .= "<div class=\"scheduleGridCompoundEmptyDIV\" style=\"height:" . ($thisCBlockLength * $standardRowHeight) . "px\" ";
            $blockHTML .= " roomid=\"$roomId\" startTimeUnits=\"$thisSlot\" roomname=\"{$thisRoomSchedArray[$thisKey]["roomname"]}\">&nbsp;</div>";
            $blockHTML .= "</td>";
            $ScheduledUpTo = $thisRoomSchedArray[$thisKey]["startTimeUnits"];
        }
    }
}

function renderComplicatedBlock($roomId) {
    global $thisSlotLength, $thisSlotBeginUnits, $thisSlotEndUnits, $blockHTML, $standardRowHeight;
    $blockHTML = "<td class=\"schedulerGridRoom schedulerGridSlot\" complicatedBlock=\"true\"";
    if ($thisSlotLength > 1) {
        $blockHTML .= " rowspan=\"$thisSlotLength\"";
    }
    $blockHTML .= ">";
    $blockHTML .= "<div class=\"scheduleGridComplexDIV\" style=\"height:" . ($thisSlotLength * $standardRowHeight) . "px;\" roomid=\"$roomId\" ";
    $blockHTML .= "startTimeUnits=\"$thisSlotBeginUnits\" endTimeUnits=\"$thisSlotEndUnits\">";
    $blockHTML .= "Block too complicated to render</div></td>";
}

function emptySchedBlock($roomId, $roomname) {
    global $thisSlotLength, $thisSlotBeginUnits, $thisSlotEndUnits, $standardRowHeight;
    $blockHTML = "<td class=\"schedulerGridRoom schedulerGridSlot\"";
    if ($thisSlotLength > 1) {
        $blockHTML .= " rowspan=\"$thisSlotLength\"";
    }
    $blockHTML .= ">";
    $blockHTML .= "<div class=\"scheduleGridEmptyDIV\" style = \"height:" . ($thisSlotLength * $standardRowHeight) . "px\" ";
    $blockHTML .= "roomid=\"$roomId\" startTimeUnits=\"$thisSlotBeginUnits\" endTimeUnits=\"$thisSlotEndUnits\" roomname=\"$roomname\">";
    $blockHTML .= "&nbsp;</div></td>";
    return $blockHTML;
}

function retrieveSessionInfo() {
    global $message_error;
    $ConStartDatim = CON_START_DATIM;
    $sessionid = isset($_POST["sessionid"]) ? $_POST["sessionid"] : false;
    $query["sessions"] = <<<EOD
SELECT
        S.sessionid,
        S.title,
        S.progguiddesc,
        S.notesforprog,
        TR.trackname,
        TY.typename,
        D.divisionname,
        DATE_FORMAT(ADDTIME('$ConStartDatim',SCH.starttime),'%a %l:%i %p') AS starttime,
        DATE_FORMAT(ADDTIME('$ConStartDatim',ADDTIME(SCH.starttime, S.duration)),'%a %l:%i %p') AS endtime,
        TIME_FORMAT(S.duration, '%H:%i') AS duration,
        SCH.roomid,
        R.roomname,
        (SELECT
                GROUP_CONCAT(TA.tagname SEPARATOR ', ') AS taglist
            FROM
                     SessionHasTag SHT
                JOIN Tags TA USING (tagid)
            WHERE
                SHT.sessionid = $sessionid
        ) AS taglist
    FROM
                  Sessions S
             JOIN Tracks TR USING (trackid)
             JOIN Types TY USING (typeid)
             JOIN Divisions D ON (D.divisionid = S.divisionid)
        LEFT JOIN Schedule SCH USING (sessionid)
        LEFT JOIN Rooms R USING (roomid)
    WHERE
        S.sessionid = $sessionid;
EOD;
    $query["participants"] = <<<EOD
SELECT
        POS.moderator,
        CD.badgename,
        P.badgeid,
        COALESCE(P.pubsname, CONCAT(CD.firstname, ' ', CD.lastname)) AS participantname
    FROM
             ParticipantOnSession POS
        JOIN Participants P USING (badgeid)
        JOIN CongoDump CD USING (badgeid)
    WHERE
        POS.sessionid = $sessionid;
EOD;
    $resultXML = mysql_query_XML($query);
    if (!$resultXML) {
        RenderErrorAjax($message_error);
        exit();
    }
    header("Content-Type: text/html");
    RenderXSLT('schedulerRetrSessInfo.xsl', array(), $resultXML);
    exit();
}

function editSchedule() {
    global $linki, $message, $message_error;
    //usleep(500000);
    $returnTable = isset($_POST["returnTable"]) ? $_POST["returnTable"] : false;
    $editsArray = isset($_POST["editsArray"]) ? $_POST["editsArray"] : array();
    $roomsToDisplayArray = isset($_POST["roomsToDisplayArray"]) ? $_POST["roomsToDisplayArray"] : array();
    if (count($editsArray) == 0) {
        exit();
    }
    $name = "";
    $email = "";
    get_name_and_email($name, $email); // populates them from session data or db as necessary
    $name = mysqli_real_escape_string($linki, $name);
    $email = mysqli_real_escape_string($linki, $email);
    $badgeid = mysqli_real_escape_string($linki, $_SESSION['badgeid']);
    $addToScheduleArray = array(); // this is used for the conflict checker only
    $deleteScheduleIds = array(); // this is used for the conflict checker only
    $deleteScheduleIdList = ""; // these are actually removed from the schedule with a single query -- should include deletes and reschedules
    $deleteSessionIdList = ""; // this is for updating the SessionEditHistory table -- should include only actual deletes
    $SchedInsQueryPreamble = "INSERT INTO Schedule (sessionid, roomid, starttime) VALUES ";
    $SchedInsQueryArray = array();
    $SEHInsQu = "INSERT INTO SessionEditHistory (sessionid, badgeid, name, email_address, sessioneditcode, statusid, editdescription) VALUES ";
    $SEHInsQu2 = "";
    //  status 3 is "scheduled"
    $SessStatSchedQu = "UPDATE Sessions SET statusid = 3 WHERE sessionid IN (";
    $newSchedIdArray = array();
    foreach ($editsArray as $i => $thisEdit) {
        if ($thisEdit["action"] == "insert") {
            $addToScheduleArray[$thisEdit["sessionid"]] = $thisEdit["starttimeunits"] * 30; // convert to minutes from start of con for conflict checker
            $SchedInsQueryArray[$thisEdit["sessionid"]] = $SchedInsQueryPreamble . "({$thisEdit["sessionid"]}, {$thisEdit["roomid"]},'" . floor($thisEdit["starttimeunits"] / 2) . ":" . (($thisEdit["starttimeunits"] % 2 == 1) ? "30" : "00") . ":00');";
            // session edit code 4 is "Add to schedule", status 3 is scheduled
            $SEHInsQu2 .= "({$thisEdit["sessionid"]}, \"$badgeid\", \"$name\", \"$email\", 4, 3, \"" . timeDescFromUnits($thisEdit["starttimeunits"]) . " in {$thisEdit["roomname"]} ({$thisEdit["roomid"]})\"),";
            $SessStatSchedQu .= "{$thisEdit["sessionid"]},";
        } elseif ($thisEdit["action"] == "delete") {
            $deleteScheduleIds[] = $thisEdit["scheduleid"];
            $deleteScheduleIdList .= $thisEdit["scheduleid"] . ",";
            $deleteSessionIdList .= $thisEdit["sessionid"] . ",";
            // session edit code 5 is "Remove from schedule", status 2 is vetted
            $SEHInsQu2 .= "({$thisEdit["sessionid"]}, \"$badgeid\", \"$name\", \"$email\", 5, 2, \"Removed from room {$thisEdit["roomname"]} ({$thisEdit["roomid"]})\"),";
        } elseif ($thisEdit["action"] == "reschedule") {
            $addToScheduleArray[$thisEdit["sessionid"]] = $thisEdit["starttimeunits"] * 30; // convert to minutes from start of con for conflict checker
            $SchedInsQueryArray[$thisEdit["sessionid"]] = $SchedInsQueryPreamble . "({$thisEdit["sessionid"]}, {$thisEdit["roomid"]},'" . floor($thisEdit["starttimeunits"] / 2) . ":" . (($thisEdit["starttimeunits"] % 2 == 1) ? "30" : "00") . ":00');";
            $deleteScheduleIds[] = $thisEdit["scheduleid"];
            $deleteScheduleIdList .= $thisEdit["scheduleid"] . ",";
            // session edit code 7 is "Rescheduled", status 3 is scheduled
            $SEHInsQu2 .= "({$thisEdit["sessionid"]}, \"$badgeid\", \"$name\", \"$email\", 7, 3, \"" . timeDescFromUnits($thisEdit["starttimeunits"]) . " in {$thisEdit["roomname"]} ({$thisEdit["roomid"]})\"),";
            $SessStatSchedQu .= "{$thisEdit["sessionid"]},";
        }
    }
    $deleteScheduleIdList = substr($deleteScheduleIdList, 0, -1); //drop extra trailing comma
    $deleteSessionIdList = substr($deleteSessionIdList, 0, -1); //drop extra trailing comma
    // $SchedInsQuP2 = substr($SchedInsQuP2,0,-1); //drop extra trailing comma
    $SEHInsQu2 = substr($SEHInsQu2, 0, -1); //drop extra trailing comma
    $noconflicts = check_room_sched_conflicts($deleteScheduleIds, $addToScheduleArray);
    // details of conflicts stored in $message
    $warnMsg = $message; // save for use later

    if ($deleteScheduleIdList != "") {
        $deleteQuery = "DELETE FROM Schedule WHERE scheduleid in ($deleteScheduleIdList);";
        $result = mysqli_query_with_error_handling($deleteQuery, true, true);
    }
    if ($deleteSessionIdList != "") {
        //  status 2 is "vetted"
        $SessStatVettedQu = "UPDATE Sessions SET statusid = 2 WHERE sessionid IN ($deleteSessionIdList);";
        $result = mysqli_query_with_error_handling($SessStatVettedQu, true, true);
    }
    if (count($SchedInsQueryArray) > 0) {
        foreach ($SchedInsQueryArray as $thisSessionId => $thisQuery) {
            $result = mysqli_query_with_error_handling($thisQuery, true, true);
            $SchedInsQueryArray[$thisSessionId] = mysqli_insert_id($linki);
        }
        $SessStatSchedQu = substr($SessStatSchedQu, 0, -1) . ");"; //drop extra trailing comma and close quert
        $result = mysqli_query_with_error_handling($SessStatSchedQu, true, true);
    }
    if ($SEHInsQu2) {
        $SEHInsQu = $SEHInsQu . $SEHInsQu2 . ";";
        $result = mysqli_query_with_error_handling($SEHInsQu, true, true);
    }
    if ($returnTable == "true") {
        retrieveRoomsTable();
        echo "<div id=\"warningsDivContent\">$warnMsg</div>";
    } else {
        echo($warnMsg);
        foreach ($SchedInsQueryArray as $thisSessionId => $thisScheduleId) {
            echo "<div class=\"insertedScheduleId\" sessionId=\"$thisSessionId\" scheduleId=\"$thisScheduleId\"></div>";
        }

    }
}

function retrieveSessions() {
    global $linki, $message_error;
    $currSessionIdArray = getArrayOfInts("currSessionIdArray", array());
    $trackId = getInt("trackId");
    $tagIds = getArrayOfInts("tagIds", array());
    $typeId = getInt("typeId");
    $divisionId = getInt("divisionId");
    $sessionId = getInt("sessionId");
    $title = mysqli_real_escape_string($linki, getString("title"));
    $tagmatch = getString("tagmatch");
    $query["sessions"] = <<<EOD
SELECT
        S.sessionid,
        S.title,
        S.progguiddesc,
        TR.trackname,
        TY.typename,
        D.divisionname,
        FLOOR((HOUR(S.duration) * 60 + MINUTE(S.duration) + 29) / 30) AS durationUnits,
        S.duration
    FROM
             Sessions S
        JOIN Tracks TR USING (trackid)
        JOIN Types TY USING (typeid)
        JOIN Divisions D ON (D.divisionid = S.divisionid)
    WHERE
            S.statusid IN (2,3,7)
        AND NOT EXISTS (
            SELECT * FROM Schedule SCH WHERE S.sessionid = SCH.sessionid
        )
EOD;
    if ($trackId !== false && $trackId !== 0) {
        $query["sessions"] .= " AND S.trackid = $trackId";
    }
    if ($typeId !== false && $typeId !== 0) {
        $query["sessions"] .= " AND S.typeid = $typeId";
    }
    if ($divisionId !== false && $divisionId !== 0) {
        $query["sessions"] .= " AND S.divisionid = $divisionId";
    }
    if ($sessionId !== false && $sessionId !== 0) {
        $query["sessions"] .= " AND S.sessionid = $sessionId";
    }
    if ($title !== "") {
        $query["sessions"] .= " AND S.title LIKE '%$title%'";
    }
    if (count($currSessionIdArray) > 0) {
        $currSessionIdList = implode(",", $currSessionIdArray);
        $query["sessions"] .= " AND S.sessionid NOT IN ($currSessionIdList)";
    }
    if (count($tagIds) > 0) {
        if ($tagmatch === 'all') {
            foreach ($tagIds as $tag) {
                $query["sessions"] .= " AND EXISTS (SELECT * FROM SessionHasTag WHERE sessionid = S.sessionid AND tagid = $tag)";
            }
        } else {
            $tagidList = implode(',', $tagIds);
            $query["sessions"] .= " AND EXISTS (SELECT * FROM SessionHasTag WHERE sessionid = S.sessionid AND tagid IN ($tagidList))";
        }
    }
    $resultXML = mysql_query_XML($query);
    if (!$resultXML) {
        RenderErrorAjax($message_error);
        exit();
    }
    //echo($resultXML->saveXML());
    //exit();
    $xpath = new DOMXpath($resultXML);
    $numRows = $xpath->evaluate("count(/doc/query/row)");
    // signal found no new sessions
    if ($numRows == 0) {
        header("Content-Type: text");
        echo "noNewSessionsFound";
        exit();
    }
    header("Content-Type: text/html");
    RenderXSLT('schedulerRetrSess.xsl', array(), $resultXML);
    exit();
}

// Start here.  Should be AJAX requests only
if (!$ajax_request_action = $_POST["ajax_request_action"]) {
    exit();
}
switch ($ajax_request_action) {
    case "editSchedule":
        editSchedule();
        break;
    case "retrieveRoomsTable":
        retrieveRoomsTable();
        break;
    case "retrieveSessionInfo":
        retrieveSessionInfo();
        break;
    case "retrieveSessions":
        retrieveSessions();
        break;
    default:
        exit();
}
?>
