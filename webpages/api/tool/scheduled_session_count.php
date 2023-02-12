<?php

require_once (__DIR__ . '/../../config/db_name.php');

require_once(__DIR__ . '/../db_support_functions.php');
require_once(__DIR__ . '/../authentication.php');
require_once(__DIR__ . '/../http_session_functions.php');
require_once(__DIR__ . '/../../schedule_functions.php');

$db = connect_to_db(true);
start_session_if_necessary();
$authentication = new Authentication();
try {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405);
    } else if ($authentication->isProgrammingStaff()) {
        $sessions = ScheduledSession::findAllScheduledSessionsWithParticipants($db);
        $result = array("count" => count($sessions));

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