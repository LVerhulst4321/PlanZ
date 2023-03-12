<?php

namespace PlanZ\Module;

require_once __DIR__ . "/../module_base.php";
require_once __DIR__ . "/../../tool_model.php";

use PlanZ\ModuleBase;
use PlanZ\Tool;

class RoomScheduleModule extends ModuleBase {

    /**
     * {@inheritDoc}
     */
    public static function getTools(): array {
        $result = [];

        $result[] = new Tool("Room Schedule", "Produce a printable version of the room schedule, by day.", "printRoomScheduleConfig.php");
        return $result;
    }
}

?>
