<?php
// Copyright (c) 2022 BC Holmes. All rights reserved. See copyright document for more details.

global $title;
$title = "Time Slots";

require_once('StaffCommonCode.php'); // Checks for staff permission among other things
require_once('time_slot_functions.php');
require_once('schedule_table_renderer.php');

class TimeSlot implements ScheduleCellData {
    public $day;
    public $roomId;
    public $startTime;
    public $endTime;
    public $divisionName;
    public $room;

    function getData() {
        return $this->divisionName;
    }

    function getColumnWidth() {
        return $this->room ? $this->room->getColumnWidth() : 1;
    }

    function getStartIndex() {
        return time_to_row_index($this->startTime);
    }

    function getEndIndex() {
        return time_to_row_index($this->endTime);
    }

    function getRowHeight() {
        return $this->getEndIndex() - $this->getStartIndex();
    }
}

class TimeSlotDataProvider implements ScheduleCellDataProvider {

    private $timeSlots;

    public function __construct($timeSlots) {
        $this->timeSlots = $timeSlots;
    }

    public function findFirstStartIndexForDay($day) {
        $slots = $this->filterByDay($day);
        $result = 9999;
        foreach ($slots as $slot) {
            if ($slot->getStartIndex() < $result) {
                $result = $slot->getStartIndex();
            }
        }
        return $result;
    }
    public function findLastEndIndexForDay($day) {
        $slots = $this->filterByDay($day);
        $result = 0;
        foreach ($slots as $slot) {
            if ($slot->getEndIndex() > $result) {
                $result = $slot->getEndIndex();
            }
        }
        return $result;
    }
    public function isCellDataAvailableForDay($day) {
        $slots = $this->filterByDay($day);
        return count($slots) > 0;
    }
    public function getCellDataFor($index, $column, $day) {
        $slots = $this->filterByDay($day);
        $result = null;

        foreach ($slots as $slot) {
            if ($slot->room == null) {
                // it's probably a room that has no panels
            } else if ($slot->room->columnNumber <= $column && ($slot->room->columnNumber + $slot->room->getColumnWidth()) > $column
                    && $slot->getStartIndex() <= $index && $slot->getEndIndex() > $index) {
                $result = $slot;
                break;
            }
        }
    
        return $result;
    }

    function filterByDay($day) {
        $result = array();
    
        foreach ($this->timeSlots as $slot) {
            if ($slot->day == $day) {
                $result[] = $slot;
            }
        }
    
        return $result;
    }    
}

function select_rooms() {
    $query = <<<EOD
    SELECT r.roomname, r.roomid, r.is_online, r.area, r.display_order, r.parent_room
      FROM Rooms r
    WHERE r.is_scheduled = 1
      AND r.roomid in (select roomid from room_to_availability)
    ORDER BY display_order;
EOD;
    if (!$result = mysqli_query_exit_on_error($query)) {
        exit;
    } else {
        $temp = array();
        while ($row = mysqli_fetch_array($result)) {
            $room = new Room();
            $room->roomName = $row["roomname"];
            $room->roomId = $row["roomid"];
            $room->area = $row["area"];
            $room->isOnline = $row["is_online"] == 'Y' ? true : false;
            $room->displayOrder = $row["display_order"];
            $room->parentRoomId = $row["parent_room"];
            $room->children = array();
            $temp[$room->roomId] = $room;
        }

        return $temp;
    }
}

function select_time_slots($allRooms) {

    $query = <<<EOD
    SELECT r.roomid, r2a.day, s.start_time, s.end_time, d.divisionid, d.divisionname
      FROM Rooms r,
           room_to_availability r2a,
           room_availability_schedule a,
           room_availability_slot s,
           Divisions d
    WHERE r.is_scheduled = 1
      AND r.roomid = r2a.roomid
      AND r2a.availability_id = a.id
      AND s.availability_schedule_id = a.id
      AND d.divisionid = s.divisionid
      ;
EOD;
    if (!$result = mysqli_query_exit_on_error($query)) {
        exit;
    } else {
        $slots = array();
        while ($row = mysqli_fetch_array($result)) {
            $slot = new TimeSlot();
            $slot->roomId = $row["roomid"];
            $slot->room = $allRooms[$row["roomid"]];
            $slot->startTime = $row["start_time"];
            $slot->endTime = $row["end_time"];
            $slot->day = $row["day"];
            $slot->divisionName = $row["divisionname"];
            $slots[] = $slot;
        }
        return $slots;
    }
}

function render_table($rooms, $slots) {
    if (empty($slots)) {
        echo "<p>No slots are defined for the auto-scheduler.</p>";
        return;
    }
    $dataProvider = new TimeSlotDataProvider($slots);
    if (empty($rooms)) {
        echo "<p>No rooms are set up for the auto-scheduler.</p>";
        return;
    }
    $renderer = new ScheduleTableRenderer($rooms, $dataProvider);
    $renderer->showRoomArea = true;
    $renderer->renderTable();
}

$rooms = select_rooms();
$collatedRooms = Room::collateParentsAndAssignColumns($rooms);
$slots = select_time_slots($rooms);

staff_header($title, true);
?>

<div class="card">
    <div class="card-header">
        <h4>Time Slots</h4>
    </div>
    <div class="card-body">
        <p>The auto-scheduler uses the following time slots to help allocated panels:</p>

<?php
    render_table($collatedRooms, $slots);
?>
    </div>
</div>

<?php
    staff_footer();
?>