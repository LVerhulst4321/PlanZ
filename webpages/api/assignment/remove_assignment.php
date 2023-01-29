<?php
// Copyright (c) 2022 BC Holmes. All rights reserved. See copyright document for more details.
// This function serves as a REST API to access session history information.

if (!include ('../../config/db_name.php')) {
    include ('../../config/db_name.php');
}

require_once('../http_session_functions.php');
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
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $authentication->isProgrammingStaff()) {
        $json_string = file_get_contents('php://input');
        $json = json_decode($json_string, true);

        if (array_key_exists('sessionId', $json) && array_key_exists('badgeId', $json)) {

            $assignment = ParticipantAssignment::findAssignmentForSessionByBadgeId($db, $json["sessionId"], $json["badgeId"]);
            if ($assignment != null) {
                ParticipantAssignment::removeAssignment($db, $assignment, $authentication);
                http_response_code(204);
            } else {
                http_response_code(204);
            }
        } else {
            http_response_code(400);
        }
    } else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        http_response_code(401);
    } else {
        http_response_code(405);
    }
} finally {
    $db->close();
}
?>