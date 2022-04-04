<?php
// Copyright (c) 2022 BC Holmes. All rights reserved. See copyright document for more details.
// This function serves as a REST API to access volunteer shifts

if (!include ('../../config/db_name.php')) {
    include ('../../config/db_name.php');
}
require_once('../http_session_functions.php');
require_once('../db_support_functions.php');
require_once('../format_functions.php');
require_once('../../data_functions.php');
require_once('./volunteer_job_model.php');
require_once('./volunteer_shift_model.php');

start_session_if_necessary();
$db = connect_to_db(true);
try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && isLoggedIn()) {
        $shifts = VolunteerShift::findAll($db);
        $result = [];
        foreach ($shifts as $s) {
            $result[] = $s->asArray();
        }

        header('Content-type: application/json; charset=utf-8');
        $json_string = json_encode(array("shifts" => $result, "context" => array("timezone" => PHP_DEFAULT_TIMEZONE)));
        echo $json_string;

    } else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        http_response_code(401);
    } else {
        http_response_code(405);
    }

} finally {
    $db->close();
}
?>