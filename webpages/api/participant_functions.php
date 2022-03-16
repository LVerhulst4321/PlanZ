<?php

function get_name($dbobject) {
    if (isset($dbobject->badgename) && $dbobject->badgename !== '') {
        return $dbobject->badgename;
    } else {
        return $dbobject->firstname." ".$dbobject->lastname;
    }
}

?>