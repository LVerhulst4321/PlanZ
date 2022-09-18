<?php
// Copyright (c) 2022 BC Holmes. All rights reserved. See copyright document for more details.
// This function serves as a REST API to access session history information.

require_once ('../../config/db_name.php');

require_once('../http_session_functions.php');
require_once('./session_model.php');
require_once('../participant_assignment_model.php');
require_once('../../db_exceptions.php');
require_once('../db_support_functions.php');
require_once('../format_functions.php');
require_once('../../data_functions.php');
require_once('../../name.php');
require_once('../authentication.php');

start_session_if_necessary();
$db = connect_to_db(true);
$authentication = new Authentication();
try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && $authentication->isProgrammingStaff() && array_key_exists('sessionId', $_REQUEST)) {

        $sessionId = $_REQUEST['sessionId'];
        $session = Session::findById($db, $sessionId);
        if ($session != null) {
            $assignments = ParticipantAssignment::findAssignmentsForSession($db, $sessionId);

            header('Content-type: application/json; charset=utf-8');
            $json_string = json_encode(array("session" => $session->asArray(), "assignments" => ParticipantAssignment::toJsonArray($assignments)));
            echo $json_string;
        } else {
            http_response_code(400);
        }

    } else if ($_SERVER['REQUEST_METHOD'] === 'GET' && !$authentication->isLoggedIn()) {
        http_response_code(401);
    } else if ($_SERVER['REQUEST_METHOD'] === 'GET' && !$authentication->isProgrammingStaff()) {
        http_response_code(403);
    } else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        http_response_code(400);
    } else {
        http_response_code(405);
    }
} finally {
    $db->close();
}