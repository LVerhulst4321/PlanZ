<?php
// Copyright (c) 2022 BC Holmes. All rights reserved. See copyright document for more details.

global $title;
$title = "Time Slots";

require_once('StaffCommonCode.php'); // Checks for staff permission among other things
require_once('time_slot_functions.php');
require_once('schedule_table_renderer.php');

class ScheduleItem implements ScheduleCellData {
    public $title;
    public $roomId;
    public $startTime;
    public $duration;
    public $trackName;
    public $room;
    public $sessionId;

    function getDay() {
        $index = time_to_row_index($this->startTime);
        $day = floor($index / (24 * 4));
        $hour = $index % (24 * 4);
        if ($hour < (8 * 4)) {
            $day -= 1;
        }
        return $day;
    }
    function getData() {
        return "<div><a href=\"/EditSession.php?id=" . $this->sessionId . "\">" . $this->title . "</a></div><div class=\"small\">" . $this->trackName . "</div>";
    }

    function getColumnWidth() {
        return $this->room ? $this->room->getColumnWidth() : 1;
    }

    function getStartIndex() {
        $daysIndex = $this->getDay() * (24 * 4);
        return time_to_row_index($this->startTime) - $daysIndex;
    }

    function getEndIndex() {
        $start = $this->getStartIndex();
        $duration = time_to_row_index($this->duration);
        return $start + $duration;
    }

    function getRowHeight() {
        return $this->getEndIndex() - $this->getStartIndex();
    }
}

class ScheduleItemDataProvider implements ScheduleCellDataProvider {

    private $items;

    public function __construct($items) {
        $this->items = $items;
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
    
        foreach ($this->items as $item) {
            if ($item->getDay() == $day) {
                $result[] = $item;
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
      AND r.roomid in (select roomid from Schedule)
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

function select_schedule_items($allRooms) {

    $query = <<<EOD
    SELECT sch.roomid, sess.title, sch.starttime, t.trackname, sess.duration, sess.sessionid
      FROM Sessions sess
      JOIN Schedule sch USING (sessionid)
      JOIN Tracks t USING (trackid)
      ;
EOD;
    if (!$result = mysqli_query_exit_on_error($query)) {
        exit;
    } else {
        $slots = array();
        while ($row = mysqli_fetch_array($result)) {
            $slot = new ScheduleItem();
            $slot->roomId = $row["roomid"];
            $slot->room = $allRooms[$row["roomid"]];
            $slot->startTime = $row["starttime"];
            $slot->duration = $row["duration"];
            $slot->title = $row["title"];
            $slot->sessionId = $row["sessionid"];
            $slot->trackName = $row["trackname"];
            $slots[] = $slot;
        }
        return $slots;
    }
}

function render_table($rooms, $items) {
    if (empty($items)) {
        echo "<p>No items are currently scheduled</p>";
        return;
    }
    $dataProvider = new ScheduleItemDataProvider($items);
    if (empty($rooms)) {
        echo "<p>No rooms are currently set up.</p>";
        return;
    }
    $renderer = new ScheduleTableRenderer($rooms, $dataProvider);
    $renderer->renderTable();
}

$rooms = select_rooms();
$collatedRooms = Room::collateParentsAndAssignColumns($rooms);
$items = select_schedule_items($rooms);

staff_header($title, true);
?>

<div class="card">
    <div class="card-header">
        <h4>Current Schedule</h4>
    </div>
    <div class="card-body">
        <p>The following sessions have been scheduled:</p>

<?php
    render_table($collatedRooms, $items);
?>
    </div>
</div>

<?php
    staff_footer();
?>