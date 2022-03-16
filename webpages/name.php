<?php

class PersonName {
    public $firstName;
    public $lastName;
    public $badgeName;
    public $pubsName;

    function getPubsName() {
        if ($this->pubsName) {
            return $this->pubsName;
        } else {
            return $this->getBadgeName();
        }
    }

    function getBadgeName() {
        if ($this->badgeName) {
            return $this->badgeName;
        } else {
            return $this->getFirstNameLastName();
        }
    }

    function getFirstNameLastName() {
        $result = "";
        if ($this->firstName) {
            $result = $this->firstName;
        }

        if ($this->lastName) {
            if (mb_strlen($result) != 0) {
                $result = $result . " ";
            }
            $result = $result . $this->lastName;
        }
        return $result;
    }
}

?>