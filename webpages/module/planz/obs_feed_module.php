<?php

/**
 * Module to provide feed to OBS per room.
 *
 * PHP version 7.1+
 *
 * @category Module
 * @package  PlanZ
 * @author   James Shields <james@lostcarpark.com>
 * @license  Zambia Software Licence
 * @link     https://github.com/LVerhulst4321/PlanZ
 */
namespace PlanZ\Module;

require_once __DIR__ . "/../../tool_model.php";

use Tool;

/**
 * OBS Feed module for PlanZ.
 *
 * @category Module
 * @package  PlanZ
 * @author   James Shields <james@lostcarpark.com>
 * @license  Zambia Software Licence
 * @link     https://github.com/LVerhulst4321/PlanZ
 */
class ObsFeedModule
{
    /**
     * Function to get tools entry menu.
     *
     * @return array
     */
    public static function getTools(): array
    {
        $result = [];

        $result[] = new Tool(
            "OBS Feeds",
            "Create extract of sessions per day/room for OBS.",
            "module/obs/feeds.php",
        );
        return $result;
    }
}

?>
