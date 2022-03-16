<?php

class Room {
    public $roomId;
    public $roomName;
    public $area;
    public $isOnline;
    public $displayOrder;
    public $columnNumber;
    public $parentRoomId;
    public $children;

    function getColumnWidth() {
        $width = 0;
        if ($this->children) {
            foreach ($this->children as $child) {
                $width += $child->getColumnWidth();
            }
        }
        return $width == 0 ? 1 : $width;
    }

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
}

function time_to_row_index($time, $rowSize = 15) {
    $index1 = strpos($time, ':');
    $hours = intval(substr($time, 0, $index1));
    $minutes = intval(substr($time, $index1+1, 2));
    return ($hours * 60 + $minutes) / $rowSize;
}

?>