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

function convert_database_date_no_time_to_date($db_date) {
    if ($db_date) {
        $date = date_create_from_format('Y-m-d', $db_date);
        $date->setTimezone(new DateTimeZone(PHP_DEFAULT_TIMEZONE));
        return $date;
    } else {
        return null;
    }
}

function convert_iso_date_to_date($date) {
    if ($date) {
        if (substr($date, -1) === 'Z') {
            $date = substr($date, 0, -1) . '+00:00';
        }
        $result = date_create_from_format("Y-m-d\\TH:i:s.vP", $date);
        $result->setTimezone(new DateTimeZone(PHP_DEFAULT_TIMEZONE));
        return $result;
    } else {
        return null;
    }
}

?>