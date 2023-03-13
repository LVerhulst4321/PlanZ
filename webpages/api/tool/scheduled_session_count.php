<?php

require_once (__DIR__ . '/../../config/db_name.php');

require_once(__DIR__ . '/../db_support_functions.php');
require_once(__DIR__ . '/../authentication.php');
require_once(__DIR__ . '/../http_session_functions.php');
require_once(__DIR__ . '/../../schedule_functions.php');

function count_sessions($sessions, $day) {
    if ($day) {
        $count = 0;
        foreach ($sessions as $s) {
            $formattedDay = date_format($s->starttime_unformatted, 'Y-m-d');
            if ($formattedDay == $day) {
                $count = $count + 1;
            }
        }
        return $count;
    } else {
        return count($sessions);
    }
}

$db = connect_to_db(true);
date_default_timezone_set(PHP_DEFAULT_TIMEZONE);
start_session_if_necessary();
$authentication = new Authentication();
try {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405);
    } else if ($authentication->isProgrammingStaff()) {
        $day = array_key_exists("day", $_REQUEST) ? $_REQUEST["day"] : null;
        $sessions = ScheduledSession::findAllScheduledSessionsWithParticipants($db);
        $result = array("count" => count_sessions($sessions, $day));

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