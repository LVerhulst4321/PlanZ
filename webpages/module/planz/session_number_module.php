<?php

namespace PlanZ\Module;

require_once(__DIR__ . "/../../tool_model.php");

use Tool;

class SessionNumberModule extends Tool {

    /**
     * {@inheritDoc}
     */
    public static function getTools(): array {
        $result = [];

        $result[] = new SessionNumberModule("Assign Session Numbers", "Assign simple session numbers for publications.", "assignSessionNumberConfig.php");
        return $result;
    }
}

?>
