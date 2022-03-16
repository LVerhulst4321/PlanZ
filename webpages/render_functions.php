<?php
// Copyright (c) 2007-2021 Peter Olszowka. All rights reserved. See copyright document for more details.


// RenderPrecis display requires:  a query result containing rows with these fields IN THIS ORDER:
// $sessionid, $trackname, $typename, $title, $duration, $estatten, $progguiddesc, $persppartinfo, $starttime, $roomname, $statusname, $taglist, $notesforprog, $notesforpart, $servicenotes, $pubstatusname, $sessionhistory
// it displays the precis view of the data.
function RenderPrecis($result, $showlinks, $href, $sessionSearchArray) {
    $html = RenderPrecisToString($result, $showlinks, $href, $sessionSearchArray);
    echo $html;
}

function RenderPrecisToString($result, $showlinks, $href, $sessionSearchArray, $oneLinePerSession = false) {
    $html = "";

    $html .= "<div class=\"alert alert-info mt-3\">Generated on: " . date('d-M-Y h:i A') . "</div>\n";
    if (mysqli_num_rows($result) < 1) {
        $html .=  "<p class=\"alert alert-warning\">No matching results found.</p>";
        return $html;
    }
    $html .=  "<div class=\"card\"><div class=\"card-body\">";
    if ($href) {
        $params = "";
        foreach ($sessionSearchArray as $key => $value) {
            if ($value && $value !== "") {
                if ($key === "statusidList") {
                    $params .= ("&status=" .urlencode($value));
                } else if ($key === "typeidList") {
                    $params .= ("&type=" .urlencode($value));
                } else if ($key === "trackidList") {
                    $params .= ("&track=" .urlencode($value));
                } else if ($key === "searchTitle") {
                    $params .= ("&searchtitle=" .urlencode($value));
                } else {
                    $params .= ("&$key=" .urlencode($value));
                }
            }
        }

        $html .=  "<div class=\"text-right\"><a class=\"btn btn-secondary btn-sm\" href=\"$href?csv=csv$params\">Download CSV</a></div>";
    }
    $html .=  "<p>If a room name and time are listed, then the session is on the schedule; otherwise, not.</p>";
    $html .=  "<table class=\"table table-sm\">\n";

    $colSpan2 = $oneLinePerSession ? "" : "colspan=\"2\"";
    $colSpan6 = $oneLinePerSession ? "" : "colspan=\"6\"";
    $colSpan8 = $oneLinePerSession ? "" : "colspan=\"8\"";

    if ($oneLinePerSession) {
        $html .= "<thead><tr>";

        $html .= "<th>Id</th>";
        if (TRACK_TAG_USAGE !== "TAG_ONLY") {
            $html .= "<th>Track</th>";
        }
        $html .= "<th>Type</th>";
        $html .= "<th>Title</th>";
        $html .= "<th>Duration</th>";
        $html .= "<th>Start time</th>";
        $html .= "<th>Room</th>";
        $html .= "<th>Status</th>";
        $html .= "<th>PubStatusName</th>";
        $html .= "<th>Tags</th>";
        $html .= "<th>Description</th>";

        $html .= "<th>Prospective Participant Info</th>";
        $html .= "<th>Notes for programming</th>";
        $html .= "<th>Notes for participant</th>";
        $html .= "<th>Service Notes</th>";
        $html .= "<th>Session History</th>";

        $html .= "</tr></thead>";
    }

    while (list($sessionid, $trackname, $typename, $title, $duration, $estatten, $progguiddesc, $persppartinfo, $starttime, $roomname, $statusname, $taglist, $notesforprog, $notesforpart, $servicenotes, $pubstatusname, $sessionhistory)
        = mysqli_fetch_array($result, MYSQLI_NUM)) {
        $html .= "<tr>\n";
        $rowSpan = $oneLinePerSession ? "" : "rowspan=\"3\"";
        $html .= "  <th $rowSpan id=\"sessidtcell\">";
        if ($showlinks) {
            $html .= "<a href=\"StaffAssignParticipants.php?selsess=" . $sessionid . "\">" . $sessionid . "</a>";
        } else {
            $html .= "$sessionid";
        }
        $html .= "</th>\n";
        if (TRACK_TAG_USAGE !== "TAG_ONLY") {
            $html .= "  <td>" . $trackname . "</td>\n";
            $html .= "  <td>" . $typename . "</td>\n";
        } else {
            $html .= "  <td $colSpan2>" . $typename . "</td>\n";
        }
        $html .= "  <td style=\"font-weight:bold\">";
        if ($showlinks) {
            $html .= "<a href=\"EditSession.php?id=" . $sessionid . "\">" . htmlspecialchars($title, ENT_NOQUOTES) . "</a>";
        } else {
            $html .= htmlspecialchars($title, ENT_NOQUOTES);
        }
        $html .= "&nbsp;&nbsp;</td>\n";
        $html .= "  <td>" . $duration . "</td>\n";
        $html .= "  <td>";
        if ($roomname) {
            $html .= $roomname;
        } else {
            $html .= "&nbsp;";
        }
        $html .= "</td>\n";
        $html .= "  <td>";
        if ($starttime) {
            $html .= $starttime;
        } else {
            $html .= "&nbsp;";
        }
        $html .= "</td>\n";
        $html .= "    <td>$statusname</td>\n";
        $html .= "    <td>$pubstatusname</td>\n";
        if ($showlinks) {
            $html .= "    <td class=\"text-right\"><a class=\"btn btn-sm btn-outline-secondary\" href=\"SessionHistory.php?selsess=$sessionid\">History</a></td>\n";
        } else if (!$oneLinePerSession) {
            $html .= "<td></td>";
        }
        if (!$oneLinePerSession) {
            $html .= "</tr>\n";
            $html .= "<tr>";
        }
        $html .= "    <td $colSpan2>" . htmlspecialchars($taglist, ENT_NOQUOTES) . "</td>";
        $html .= "    <td $colSpan6>" . htmlspecialchars($progguiddesc, ENT_NOQUOTES) . "</td>";
        if (!$oneLinePerSession) {
            $html .= "</tr>\n";
        }
        if ($persppartinfo) {
            if (!$oneLinePerSession) {
                $html .= "<tr>";
                $html .= "<td></td>";
                $html .= "<td $colSpan2>Prospective Participant Info: </td>";
            }
            $html .= "<td $colSpan6>".htmlspecialchars($persppartinfo,ENT_NOQUOTES)."</td>";
            if (!$oneLinePerSession) {
                $html .= "</tr>\n";
            }
        } else {
            if (!$oneLinePerSession) {
                $html .= "<tr>";
            }
            $html .= "<td $colSpan8>&nbsp;</td>\n";
            if (!$oneLinePerSession) {
                $html .= "</tr>\n";
            }
        }
        if ($notesforprog) {
            if (!$oneLinePerSession) {
                $html .= "<tr>";
                $html .= "<td></td>";
                $html .= "<td $colSpan2>Notes for Programming Committee: </td>";
            }
            $html .= "<td $colSpan6>".htmlspecialchars($notesforprog,ENT_NOQUOTES)."</td>";
            if (!$oneLinePerSession) {
                $html .= "</tr>\n";
            }
        } else {
            if (!$oneLinePerSession) {
                $html .= "<tr>";
            }
            $html .= "<td $colSpan8>&nbsp;</td>\n";
            if (!$oneLinePerSession) {
                $html .= "</tr>\n";
            }
        }
        if ($notesforpart) {
            if (!$oneLinePerSession) {
                $html .= "<tr>";
                $html .= "<td></td>";
                $html .= "<td $colSpan2>Notes for Participants: </td>";
            }
            $html .= "<td $colSpan6>".htmlspecialchars($notesforpart,ENT_NOQUOTES)."</td>";
            if (!$oneLinePerSession) {
                $html .= "</tr>\n";
            }
        } else {
            if (!$oneLinePerSession) {
                $html .= "<tr>";
            }
            $html .= "<td $colSpan8>&nbsp;</td>\n";
            if (!$oneLinePerSession) {
                $html .= "</tr>\n";
            }
        }
        if ($servicenotes) {
            if (!$oneLinePerSession) {
                $html .= "<tr>";
                $html .= "<td></td>";
                $html .= "<td $colSpan2>Notes for Tech and Hotel: </td>";
            }
            $html .= "<td $colSpan6>".htmlspecialchars($servicenotes,ENT_NOQUOTES)."</td>";
            if (!$oneLinePerSession) {
                $html .= "</tr>\n";
            }
        } else {
            if (!$oneLinePerSession) {
                $html .= "<tr>";
            }
            $html .= "<td $colSpan8>&nbsp;</td>\n";
            if (!$oneLinePerSession) {
                $html .= "</tr>\n";
            }
        }
        if ($sessionhistory) {
            if (!$oneLinePerSession) {
                $html .= "<tr>";
                $html .= "<td></td>";
                $html .= "<td $colSpan2>Session History Notes: </td>";
            }
            $html .= "<td $colSpan6>".htmlspecialchars($sessionhistory,ENT_NOQUOTES)."</td>";
            if (!$oneLinePerSession) {
                $html .= "</tr>\n";
            }
        } else {
            if (!$oneLinePerSession) {
                $html .= "<tr>";
            }
            $html .= "<td $colSpan8>&nbsp;</td>\n";
            if (!$oneLinePerSession) {
                $html .= "</tr>\n";
            }
        }
        echo "<tr><td colspan=\"8\" class=\"border0020\">&nbsp;</td></tr>\n";
    }
    $html .= "</table>\n";
    $html .= "</div></div>";
    return $html;
}
?>
