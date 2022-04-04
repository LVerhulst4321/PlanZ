<?php
// Copyright (c) 2022 BC Holmes. All rights reserved. See copyright document for more details.
// This function serves as a REST API to access session history information.

if (!include ('../../config/db_name.php')) {
    include ('../../config/db_name.php');
}
require_once('../http_session_functions.php');
require_once('../db_support_functions.php');
require_once('../format_functions.php');
require_once('../../data_functions.php');
require_once('./volunteer_job_model.php');

function is_input_data_valid($json) {
    return array_key_exists("name", $json) && array_key_exists("description", $json);
}


start_session_if_necessary();
$db = connect_to_db(true);
try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isVolunteerSetUpAllowed()) {

        $json_string = file_get_contents('php://input');
        $json = json_decode($json_string, true);

        if (is_input_data_valid($json)) {
            $job = VolunteerJob::fromJson($json);
            VolunteerJob::persist($db, $job);
    
            http_response_code(201);
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