<?php

function convert_database_date_to_date($db_date) {
    if ($db_date) {
        $date = date_create_from_format('Y-m-d H:i:s', $db_date);
        $date->setTimezone(PHP_DEFAULT_TIMEZONE);
        return $date;
    } else {
        return null;
    }
}

?>