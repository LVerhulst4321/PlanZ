<?php
// Copyright (c) 2022 BC Holmes. All rights reserved. See copyright document for more details.
// This functionality has been inspired by code created by Piglet for the original WisConDB
// codebase (https://bitbucket.org/wiscon/wiscon/src/master/), and by Alien Planit 
// (https://github.com/annalee/alienplanit) by Annalee (https://github.com/annalee)

global $title;
$title = "Auto-Scheduler";

require_once('StaffCommonCode.php'); // Checks for staff permission among other things

function number_of_respondants() {
    $query = <<<EOD
    SELECT count(DISTINCT badgeid) as count
        FROM ParticipantSessionInterest;
EOD;
    if (!$result = mysqli_query_exit_on_error($query)) {
        exit;
    } else if (mysqli_num_rows($result) !== 1) {
        return 0;
    } else {
        list($count) = mysqli_fetch_array($result);
        return $count;
    }
}

function number_of_panels($interests) {

    $clause = "";
    if ($interests) {
        $clause = <<<EOD
            AND s.sessionid in (SELECT
                distinct PSI.sessionid
            FROM
                    ParticipantSessionInterest PSI
                JOIN Participants P USING (badgeid)
            WHERE
                P.interested = 1
                AND ((PSI.rank != 0 and PSI.rank is not null and PSI.rank != 5) OR PSI.willmoderate = 'Y')
            )
EOD;
    }
    $query = <<<EOD
    SELECT count(distinct s.sessionid)
        FROM Sessions s
        JOIN SessionStatuses ss USING (statusid)
        JOIN PubStatuses ps USING (pubstatusid)
    WHERE ss.may_be_scheduled = 1
        AND ps.pubstatusname = 'Public'
        AND s.divisionid in (select divisionid from Divisions where divisionname = 'Panels')
        $clause
EOD;
    if (!$result = mysqli_query_exit_on_error($query)) {
        exit;
    } else if (mysqli_num_rows($result) !== 1) {
        return 0;
    } else {
        list($count) = mysqli_fetch_array($result);
        return $count;
    }
}

function number_of_interested_panelists() {
    $query = <<<EOD
    SELECT count(DISTINCT badgeid) as count
        FROM ParticipantSessionInterest
        where (`rank` is not null and `rank` != 0) or willmoderate = 1;
EOD;
    if (!$result = mysqli_query_exit_on_error($query)) {
        exit;
    } else if (mysqli_num_rows($result) !== 1) {
        return 0;
    } else {
        list($count) = mysqli_fetch_array($result);
        return $count;
    }
}

function number_of_available_slots($online) {
    $onlineFlag = $online ? "Y" : "N";

    $query = <<<EOD
    SELECT count(*) as count
        FROM Rooms r, room_availability_slot s, room_availability_schedule a, room_to_availability r2a
        where r2a.roomid = r.roomid
          and r2a.availability_id = a.id
          and r.is_online = '$onlineFlag'
          and a.id = s.availability_schedule_id
          and s.divisionid in (select divisionid from Divisions where divisionname = 'Panels');
EOD;
    if (!$result = mysqli_query_exit_on_error($query)) {
        exit;
    } else if (mysqli_num_rows($result) !== 1) {
        return 0;
    } else {
        list($count) = mysqli_fetch_array($result);
        return $count;
    }
}


$countRespondants = number_of_respondants();
$countPanelists = number_of_interested_panelists();
$countPanels = number_of_panels(false);
$countPanelsWithPanelists = number_of_panels(true);
$countInPersonSlots = number_of_available_slots(false);
$countOnlineSlots = number_of_available_slots(true);

staff_header($title, true);
?>

<div class="container">
    <div class="card">
        <div class="card-header">
            <h4>Auto-Scheduler</h4>
        </div>
        <div class="card-body">
            <p>The auto-scheduler is a tool that can analyze the results of the interest survey, 
                and use those results to perform a first pass at populating the schedule.</p>
            <div class="row">
                <div class="col-md-6">
                    <table class="table table-bordered">
                        <tr>
                            <th rowspan="2">Panel suggestions</th>
                            <td>Total</td>
                            <td class="text-center"><?php echo $countPanels ?></td>
                        </tr>
                        <tr>
                            <td>With Potential Panelists</td>
                            <td class="text-center"><?php echo $countPanelsWithPanelists ?></td>
                        </tr>
                        <tr>
                            <th rowspan="2"><a href="./TimeSlot.php">Available panel slots</a></th>
                            <td>In-Person</td>
                            <td class="text-center"><?php echo $countInPersonSlots ?></td>
                        </tr>
                        <tr>
                            <td>Online</td>
                            <td class="text-center"><?php echo $countOnlineSlots ?></td>
                        </tr>
                        <tr>
                            <th colspan="2">Interest survey respondants</th>
                            <td class="text-center"><?php echo $countRespondants ?></td>
                        </tr>
                        <tr>
                            <th colspan="2">Potential panelist respondants</th>
                            <td class="text-center"><?php echo $countPanelists ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
    staff_footer();
?>