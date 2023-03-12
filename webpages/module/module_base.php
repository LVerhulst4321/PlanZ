<?php

namespace PlanZ;

/**
 * Module base class.
 *
 * PHP version 7.4+
 *
 * @category Module
 * @package  PlanZ
 * @author   James Shields <james@lostcarpark.com>
 * @license  Zambia Software Licence
 * @link     https://github.com/LVerhulst4321/PlanZ
 */
abstract class ModuleBase
{
    /**
     * Child class must contain function that returns an array of Tool objects.
     *
     * @return array
     */
    public static function getTools(): array
    {
        return [];
    }
}
