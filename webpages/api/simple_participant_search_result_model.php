<?php

require_once(__DIR__ . '/../name.php');

class SimpleParticipantSearchResult {

    public $badgeId;
    public $name;
    public $avatarSrc;
    public $registered;

    function asArray() {
        $result = array("badgeId" => $this->badgeId,
            "name" => $this->name->asArray(),
            "registered" => $this->registered,
            "links" => array("avatar" => $this->avatarSrc)
        );
        return $result;
    }

    public static function toJsonArray($results) {
        $result = [];
        foreach ($results as $r) {
            $result[] = $r->asArray();
        }
        return $result;
    }
}

?>