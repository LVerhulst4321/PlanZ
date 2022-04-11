<?php

function convert_database_date_to_date($db_date) {
    if ($db_date) {
        $date = date_create_from_format('Y-m-d H:i:s', $db_date);
        $date->setTimezone(new DateTimeZone(PHP_DEFAULT_TIMEZONE));
        return $date;
    } else {
        return null;
    }
}

function convert_iso_date_to_date($date) {
    if ($date) {
        if (substr($date, -1) === 'Z') {
            return date_create_from_format('Y-m-d\TH:i:s.v\Z', $date);
        } else {
            return date_create_from_format(DateTime::ISO8601, $date);
        }
    } else {
        return null;
    }
}

?>