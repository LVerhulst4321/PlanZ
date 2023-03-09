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
namespace PlanZ\TableTents;

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
class TableTentsModule extends Tool
{
    /**
     * {@inheritDoc}
     *
     * @return array
     */
    public static function getTools(): array
    {
        $result = [];

        $result[] = new TableTentsModule(
            "Table Tents",
            "Produce a printable version of the table tents for the various con sessions.",
            "module/table_tents/TableTentsConfig.php",
        );
        return $result;
    }
}

?>
