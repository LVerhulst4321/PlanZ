<?php
// Copyright (c) 2011-2020 Peter Olszowka. All rights reserved. See copyright document for more details.
global $message_error, $title, $linki;
$bigarray = array();
define("newroomslots", 5); // number of rows at bottom of page for new schedule entries
$title = "Maintain Room Schedule";
require_once('StaffCommonCode.php');
require_once('SubmitMaintainRoom.php');

staff_header($title, true);
$topsectiononly = true; // no room selected -- flag indicates to display only the top section of the page
$conflict = false; // initialize
if (isset($_POST["numrows"])) {
    $ignore_conflicts = (isset($_POST['override'])) ? true : false;
    if (!SubmitMaintainRoom($ignore_conflicts))
        $conflict = true;
}

if (isset($_POST["selroom"]) && $_POST["selroom"] != "0") { // room was selected by this form
    $selroomid = $_POST["selroom"];
    $topsectiononly = false;
    //unset($_SESSION['return_to_page']); // since edit originated with this page, do not return to another.
} elseif (isset($_GET["selroom"])) { // room was select by external page such as a report
    $selroomid = $_GET["selroom"];
    $topsectiononly = false;
} else {
    $selroomid = 0; // room was not yet selected.
    unset($_SESSION['return_to_page']); // since edit originated with this page, do not return to another.
}

if ($conflict != true) {
    $queryArray["rooms"] = "SELECT roomid, roomname, `function`, is_scheduled FROM Rooms ORDER BY display_order";
    if (($resultXML = mysql_query_XML($queryArray)) === false) {
        RenderErrorAjax($message_error); //header has already been sent, so can just send error message and stop.
        exit();
    }
    ?>
<div class="container">
    <div class="card"><div class="card-body">
<form id="maintain-room-sched-room-form" name="selroomform" method="POST" action="MaintainRoomSched.php">
	<div class="form-group row">
        <label for="selroom" class="col-sm-2">Select Room:</label>
        <div class="col-sm-6">
<?php RenderXSLT('MaintainRoomSched_roomSelect.xsl', array(), $resultXML); ?>
        </div>
        <div class="col-sm-4">
            <button type="submit" name="submit" class="btn btn-primary">Fetch Room</button>
        </div>
    </div>
<?php
    if (isset($_SESSION['return_to_page'])) {
        echo "<A HREF=\"" . $_SESSION['return_to_page'] . "\">Return to report</A>";
    }
?>
	<div>
        <input type="checkbox" class="checkbox adjust" id="showUnschedRmsCHK" name="showUnschedRmsCHK" value="1"
            <?php if (isset($_POST["showUnschedRmsCHK"])) echo "checked=\"checked\""?> />
        <label class="checkbox inline" for="showUnschedRmsCHK">Include unscheduled rooms</label>
	</div>
	<div class="padded text-info">For any session where you are rescheduling, please read the Notes for Programming Committee.</div>
	</form>
    </div>
    </div>
<?php
		// unset all stuff from posts so input fields get reset to blank
    for ($i = 1; $i <= newroomslots; $i++) {
        unset($_POST["day$i"]);
        unset($_POST["hour$i"]);
        unset($_POST["min$i"]);
        unset($_POST["ampm$i"]);
        unset($_POST["sess$i"]);
    }
}
if ($topsectiononly) {
?>
    </div>
<?php
    staff_footer();
    exit();
}
?>
<div class="card mt-4">
<form class="zambia-form" name="rmschdform" method="POST" action="MaintainRoomSched.php">
<input type="hidden" name="showUnschedRmsCHK" value="1" <?php if (isset($_POST["showUnschedRmsCHK"])) echo "checked=\"checked\""?> />
<?php
if ($conflict==true) {
	echo "<button type=\"submit\" name=\"override\" class=\"btn btn-danger\">Save Anyway!</button>\n";
	echo "<br><hr>\n";
}
$query = <<<EOD
SELECT
        roomid, roomname, opentime1, closetime1, opentime2, closetime2, opentime3, closetime3, opentime4, closetime4, opentime5, closetime5,
        `function`, floor, height, dimensions, area, notes
    FROM
        Rooms
    WHERE
        roomid = $selroomid;
EOD;
if (!$result = mysqli_query_exit_on_error($query)) {
    exit(); // should have exited already
}
$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
mysqli_free_result($result);
$selroomname = htmlspecialchars($row["roomname"]);
echo "<div class=\"card-header\"><h2>$selroomid - " . $selroomname . "</h2></div>";
echo "<div class=\"card-body\"><h4 class=\"label\">Open Times</h4>\n";
echo "<div class=\"border1111 lrpad lrmargin\"><p class=\"lrmargin\">";
if ($row["opentime1"] != "") {
    echo time_description($row["opentime1"]) . " through " . time_description($row["closetime1"]) . "<br />\n";
}
if ($row["opentime2"] != "") {
    echo time_description($row["opentime2"]) . " through " . time_description($row["closetime2"]) . "<br />\n";
}
if ($row["opentime3"] != "") {
    echo time_description($row["opentime3"]) . " through " . time_description($row["closetime3"]) . "<br />\n";
}
if ($row["opentime4"] != "") {
    echo time_description($row["opentime4"]) . " through " . time_description($row["closetime4"]) . "<br />\n";
}
if ($row["opentime5"] != "") {
    echo time_description($row["opentime5"]) . " through " . time_description($row["closetime5"]) . "<br />\n";
}
echo "</div>\n";
echo "<h4 class=\"label\">Characteristics</H4>\n";
echo "   <table class=\"table table-sm table-hover\">\n";
echo "      <tr>\n";
echo "         <th>Function</th>\n";
echo "         <th>Floor</th>\n";
echo "         <th>Dimensions</th>\n";
echo "         <th>Area</th>\n";
echo "         <th>Height</th>\n";
echo "         </tr>\n";
echo "      <tr>\n";
echo "         <td>".htmlspecialchars($row["function"])."</td>\n";
echo "         <td>".htmlspecialchars($row["floor"])."</td>\n";
echo "         <td>".htmlspecialchars($row["dimensions"])."</td>\n";
echo "         <td>".htmlspecialchars($row["area"])."</td>\n";
echo "         <td>".htmlspecialchars($row["height"])."</td>\n";
echo "         </tr>\n";
if ($row["notes"] != "") {
    echo "        <tr>\n";
    echo "          <td colspan=5 class=\"alert alert-info\">" . htmlspecialchars($row["notes"]) . "</td>\n";
    echo "        </tr>\n";
}
echo "      </table>\n";
echo "<h4 class=\"label\">Room Sets</h4>\n";
$query = <<<EOD
SELECT
        RS.roomsetname, RHS.capacity
    FROM
             RoomSets RS
        JOIN RoomHasSet RHS USING (roomsetid)
    WHERE
        RHS.roomid = $selroomid;
EOD;
if (!$result=mysqli_query_exit_on_error($query)) {
    exit(); //should have exited already
}
$roomSetArray = array();
while ($foo = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
    $roomSetArray[] = $foo;
}
mysqli_free_result($result);
echo "   <table class=\"table table-sm table-hover\">\n";
echo "      <tr>\n";
echo "         <th>Room Set</th>\n";
echo "         <th>Capacity</th>\n";
echo "         </tr>\n";
foreach ($roomSetArray as $roomset) {
    echo "   <tr>\n";
    echo "      <td>" . $roomset["roomsetname"] . "</td>\n";
    echo "      <td>" . $roomset["capacity"] . "</td>\n";
    echo "      </tr>\n";
}
echo "      </table>\n";
$query = <<<EOD
SELECT
        SCH.scheduleid, SCH.starttime, S.duration, SCH.sessionid, T.trackname, S.title, RS.roomsetname,
        GROUP_CONCAT(TA.tagname SEPARATOR ', ') AS taglist
    FROM
                   Schedule SCH
              JOIN Sessions S USING (sessionid)
              JOIN Tracks T USING (trackid)
              JOIN RoomSets RS USING (roomsetid)
        LEFT JOIN SessionHasTag SHT USING (sessionid)
        LEFT JOIN Tags TA USING (tagid)
    WHERE
        SCH.roomid = $selroomid
    GROUP BY
        SCH.scheduleid
    ORDER BY
        SCH.starttime;
EOD;
if (!$result = mysqli_query_exit_on_error($query)) {
    exit(); // should have exited already
}
$i = 1;
while ($bigarray[$i] = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
    $i++;
}
mysqli_free_result($result);
$numrows = --$i;

echo "<hr />\n";
echo "<h4 class=\"label\">Current Room Schedule</H4>\n";
echo "<table class=\"table table-sm\">\n";
echo "   <tr>\n";
echo "      <th>Delete</th>\n";
echo "      <th>Start Time</th>\n";
echo "      <th>Duration</th>\n";
echo "      <th>Track</th>\n";
echo "      <th>Tags</th>\n";
echo "      <th>Session ID</th>\n";
echo "      <th>Title</th>\n";
echo "      <th>Room Set</th>\n";
echo "      </tr>\n";
for ($i = 1; $i <= $numrows; $i++) {
    echo "   <tr>\n";
    echo "      <td class=\"border0010\"><input type=\"checkbox\" class=\"checkbox adjust\" name=\"del$i\" value=\"1\"></td>\n";
    echo "<input type=\"hidden\" name=\"row$i\" value=\"" . $bigarray[$i]["scheduleid"] . "\">";
    echo "<input type=\"hidden\" name=\"rowsession$i\" value=\"{$bigarray[$i]["sessionid"]}\"></td>\n";
    echo "      <td>" . time_description($bigarray[$i]["starttime"]) . "</td>\n";
    echo "      <td>" . $bigarray[$i]["duration"] . "</td>\n";
    echo "      <td>" . $bigarray[$i]["trackname"] . "</td>\n";
    echo "      <td>" . $bigarray[$i]["taglist"] . "</td>\n";
    echo "      <td> <a href=EditSession.php?id=" . $bigarray[$i]["sessionid"] . ">" . $bigarray[$i]["sessionid"] . "</td>\n";
    echo "      <td>" . $bigarray[$i]["title"] . "</td>\n";
    echo "      <td>" . $bigarray[$i]["roomsetname"] . "</td>\n";
    echo "      </tr>\n";
}
echo "   </table>\n";
echo "<h4 class=\"label\">Add To Room Schedule</H4>\n";
echo "<table id=\"add-to-room-schedule-table\" class=\"table table-sm\">\n";
?>
    <colgroup>
        <col width="15%">
        <col width="10%">
        <col width="10%">
        <col width="10%">
        <col width="55%">
    </colgroup>

<?php
$query = <<<EOD
SELECT
        S.sessionid, T.trackname, S.title
    FROM
             Sessions S
        JOIN Tracks T USING (trackid)
        JOIN SessionStatuses SS USING (statusid)
    WHERE
            SS.may_be_scheduled = 1
        AND NOT EXISTS ( SELECT *
            FROM
                Schedule
            WHERE
                sessionid = S.sessionid
          )
    ORDER BY
        T.trackname, S.sessionid
EOD;
if (!$result = mysqli_query_exit_on_error($query)) {
    exit(); // should have exited already
}
$i = 1;
while ($bigarray[$i] = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
    $i++;
}
mysqli_free_result($result);
$numsessions = --$i;
// Build array containing hours in advance.
$hours = [-1 => 'Hour&nbsp;'];
if (DISPLAY_24_HOUR_TIME) {
    foreach(array_fill(0, 24, '') as $hour => $empty) $hours[$hour] = ($hour < 10 ? '0' : '') . $hour;
}
else {
    foreach(array_fill(0, 12, '') as $hour => $empty) $hours[$hour] = $hour == 0 ? '12' : $hour;
}
// Loop through room slots to build table rows.
for ($i = 1; $i <= newroomslots; $i++) {
    echo "   <tr>\n";
    // ****DAY****
    if (CON_NUM_DAYS>1) {
        echo "<td><select class=\"form-control\" name=day$i><option value=0 ";
        if ((!isset($_POST["day$i"])) or $_POST["day$i"]==0)
            echo "selected";
        echo ">Day&nbsp;</option>";
        for ($j=1; $j<=CON_NUM_DAYS; $j++) {
            $x = longDayNameFromInt($j);
            echo"         <option value=$j ";
            if (isset($_POST["day$i"]) && $_POST["day$i"]==$j)
                echo "selected";
            echo ">$x</option>\n";
            }
        echo "</Select>&nbsp;</td>\n";
        }
	// ****HOUR****
    echo "          <td><select class=\"form-control\" name=\"hour$i\">";
    $selectedHour = $_POST["hour$i"] ?: -1;
    foreach ($hours as $key => $label) {
        echo "<option value=\"${key}\" " . ($selectedHour == $key ? 'selected' : '') . ">${label}</option>";
    }
    echo "</select></td>\n";
	// ****MIN****
    echo "          <td><select class=\"form-control\" name=\"min$i\"><option value=\"-1\" ";
	if (!isset($_POST["min$i"]))
	    $_POST["min$i"]=-1;
    if ($_POST["min$i"]==-1)
        echo "selected";
	echo">Min&nbsp;</option>";
    for ($j=0;$j<=55;$j+=5) {
        echo "<option value=$j ";
        if ($_POST["min$i"]==$j)
            echo "selected";
		echo ">".($j<10?"0":"").$j."</option>";
        }
    echo "</select></td>\n";
	// ****AM/PM**** - Only display if not using 24 hour time.
    if (!DISPLAY_24_HOUR_TIME) {
        echo "          <td><Select class=\"form-control\" name=\"ampm$i\"><option value=0 ";
        if ((!isset($_POST["ampm$i"])) or $_POST["ampm$i"]==0)
            echo "selected";
        echo ">AM&nbsp;</option><option value=1 ";
        if (isset($_POST["ampm$i"]) && $_POST["ampm$i"]==1)
            echo "selected";
        echo ">PM</option>";
        echo "</select></td>\n";
    }
    // ****Session****
    echo "      <td class=\"room-select-td\"><Select class=\"form-control\" name=\"sess$i\"><option value=\"unset\" ";
	if ((!isset($_POST["sess$i"])) or $_POST["sess$i"]=="unset")
	    echo "selected";
    echo ">Select Session</option>\n";
    for ($j=1;$j<=$numsessions;$j++) {
        echo "          <option value=\"".$bigarray[$j]["sessionid"]."\" ";
        if (isset($_POST["sess$i"]) && $_POST["sess$i"]==$bigarray[$j]["sessionid"])
            echo "selected";
		echo ">{$bigarray[$j]['trackname']} - {$bigarray[$j]['sessionid']} - {$bigarray[$j]['title']}</option>\n";
        }
    echo "</select>\n";
    echo "          </td>\n";
    echo "       </tr>\n";
    }
echo "</table>";
echo "<input type=\"hidden\" name=\"selroom\" value=\"$selroomid\">\n";
echo "<input type=\"hidden\" name=\"selroomname\" value=\"$selroomname\">\n";
echo "<input type=\"hidden\" name=\"numrows\" value=\"$numrows\">\n";
echo "<div class=\"SubmitDiv\"><button type=\"submit\" name=\"update\" class=\"btn btn-primary\">Update</button></div>\n";
echo "</form></div></div></div>\n";
staff_footer();
?>
