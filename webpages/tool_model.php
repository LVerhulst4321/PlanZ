<?php

class Tool {
    public $name;
    public $description;
    public $href;

    function __construct($name, $description, $href) {
        $this->name = $name;
        $this->description = $description;
        $this->href = $href;
    }
}

?>