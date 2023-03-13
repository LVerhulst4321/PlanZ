<?php

require_once (__DIR__ . '/../../config/db_name.php');

require_once(__DIR__ . '/../db_support_functions.php');
require_once(__DIR__ . '/../authentication.php');
require_once(__DIR__ . '/../http_session_functions.php');
require_once(__DIR__ . '/../con_info.php');

function format_days($days) {
    $result = array();
    foreach ($days as $day) {
        $result[] = array("day" => date_format($day, 'Y-m-d'), "formatted" => date_format($day, "l, d M"));
    }
    return $result;
}

$db = connect_to_db(true);
date_default_timezone_set(PHP_DEFAULT_TIMEZONE);
start_session_if_necessary();
$authentication = new Authentication();
try {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405);
    } else if ($authentication->isProgrammingStaff()) {
        $conInfo = ConInfo::findCurrentCon($db);
        $result = array("days" => format_days($conInfo->allConDays()));

        header('Content-type: application/json');
        $json_string = json_encode($result);
        echo $json_string;
    } else {
        http_response_code(401);
    }
} finally {
    $db->close();
}

?>