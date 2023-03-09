<?php

namespace PlanZ\Module;

require_once(__DIR__ . "/../../tool_model.php");

use Tool;

class RoomScheduleModule extends Tool {

    /**
     * {@inheritDoc}
     */
    public static function getTools(): array {
        $result = [];

        $result[] = new RoomScheduleModule("Room Schedule", "Produce a printable version of the room schedule, by day.", "printRoomScheduleConfig.php");
        return $result;
    }
}

?>
