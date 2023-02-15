<?php

namespace PlanZ\Module;

require_once(__DIR__ . "/../../tool_model.php");

use Tool;

class SessionNumberModule {

    public static function getTools() {
        $result = array();

        $result[] = new Tool("Assign Session Numbers", "Assign simple session numbers for publications.", "assignSessionNumberConfig.php");
        return $result;
    }
}

?>