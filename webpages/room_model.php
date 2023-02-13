<?php

require_once(__DIR__ . "/db_exceptions.php");

class Room {
    public $roomId;
    public $roomName;
    public $area;
    public $isOnline;
    public $displayOrder;
    public $columnNumber;
    public $parentRoomId;
    public $children;

    // when representing a room on a grid, this function tells us
    // how many grid columns the room should take up.
    function getColumnWidth() {
        $width = 0;
        if ($this->children) {
            foreach ($this->children as $child) {
                $width += $child->getColumnWidth();
            }
        }
        return $width == 0 ? 1 : $width;
    }

    // when representing a room on a grid, this function tells us
    // how many header rows are needed to show the room and all
    // of its child rooms.
    function getRowHeight() {
        $height = 1;

        if ($this->children) {
            $max = 0;
            foreach ($this->children as $child) {
                $max = max($max, $child->getRowHeight());
            }
            $height += $max;
        }

        return $height;
    }

    static function sortInDisplayOrder($r1, $r2) {
        return $r1->displayOrder - $r2->displayOrder;
    }

    static function collateParentsAndAssignColumns($rooms) {
        $temp = array();
        foreach ($rooms as $r) {
            $temp[$r->roomId] = $r;
        }

        foreach ($temp as $room) {
            if ($room->parentRoomId && array_key_exists($room->parentRoomId, $temp)) {
                $parent = $temp[$room->parentRoomId];
                $parent->children[] = $room;
                usort($parent->children, array("Room", "sortInDisplayOrder"));
            }
        }

        $result = array();
        foreach ($temp as $room) {
            if ($room->parentRoomId == null || !array_key_exists($room->parentRoomId, $temp)) {
                $result[] = $room;
            }
        }

        usort($result, array("Room", "sortInDisplayOrder"));
        Room::assignColumnNumbersToRooms($result, 0);
        return $result;
    }

    static function assignColumnNumbersToRooms($rooms, $column) {
        foreach ($rooms as $room) {
            $room->columnNumber = $column;
            if ($room->children && count($room->children) > 0) {
                Room::assignColumnNumbersToRooms($room->children, $column);
            }
            $column += ($room->getColumnWidth());
        }
    }

    static function findAllRoomsAndCollateParents($db) {
        $query = <<<EOD
        SELECT r.roomname, r.roomid, r.is_online, r.area, r.display_order, r.parent_room
          FROM Rooms r
        WHERE r.is_scheduled = 1
          AND r.roomid in (select roomid from Schedule)
        ORDER BY display_order;
EOD;

        $stmt = mysqli_prepare($db, $query);
        if (mysqli_stmt_execute($stmt)) {
            $temp = array();
            $resultSet = mysqli_stmt_get_result($stmt);
            while ($row = mysqli_fetch_array($resultSet)) {
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
            $stmt->close();
            return Room::collateParentsAndAssignColumns($temp);
        } else {
            throw new DatabaseSqlException("Could not process query: $query");
        }
    }
}

?>