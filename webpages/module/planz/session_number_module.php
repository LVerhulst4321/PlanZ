<?php

namespace PlanZ\Module;

require_once __DIR__ . "/../module_base.php";

use PlanZ\ModuleBase;
use PlanZ\Tool;

class SessionNumberModule extends ModuleBase {

    /**
     * {@inheritDoc}
     */
    public static function getTools(): array {
        $result = [];

        $result[] = new Tool("Assign Session Numbers", "Assign simple session numbers for publications.", "assignSessionNumberConfig.php");
        return $result;
    }
}

?>
