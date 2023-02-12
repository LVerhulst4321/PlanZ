<?php

namespace PlanZ\Module;

require_once(__DIR__ . "/../../tool_model.php");

use Tool;

class RoomScheduleModule {

    public static function getTools() {
        $result = array();

        $result[] = new Tool("Room Schedule", "Produce a printable version of the room schedule, by day.", "printRoomScheduleConfig.php");
        return $result;
    }
}

?>