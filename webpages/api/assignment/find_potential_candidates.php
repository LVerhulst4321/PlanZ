<?php
// Copyright (c) 2022 BC Holmes. All rights reserved. See copyright document for more details.
// This function serves as a REST API to look up potential candidates

if (!include ('../../config/db_name.php')) {
    include ('../../config/db_name.php');
}

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
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && $authentication->isProgrammingStaff() && array_key_exists('sessionId', $_REQUEST)
        && array_key_exists('q', $_REQUEST)) {

        $sessionId = $_REQUEST['sessionId'];
        $query = $_REQUEST['q'];

        $candidates = ParticipantAssignment::findOtherCandidateAssigneesForSession($db, $sessionId, $query);

        header('Content-type: application/json; charset=utf-8');
        $json_string = json_encode(array("candidates" => ParticipantAssignment::toJsonArray($candidates)));
        echo $json_string;

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