<?php

namespace PlanZ;

/**
 * Tool class.
 *
 * @category Module_Base
 * @package  PlanZ
 * @author   BCHolmes/James Shields <james@lostcarpark.com>
 * @license  Zambia Software Licence
 * @link     https://github.com/LVerhulst4321/PlanZ
 */
class Tool
{
    public string $name;
    public string $description;
    public string $href;

    /**
     * Constructor for module class
     *
     * @param string $name        Name of the module.
     * @param string $description Module description.
     * @param string $href        URL of module page.
     */
    public function __construct(string $name, string $description, string $href)
    {
        $this->name = $name;
        $this->description = $description;
        $this->href = $href;
    }
}

?>
