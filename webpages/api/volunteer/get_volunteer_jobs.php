<?php
// Copyright (c) 2022 BC Holmes. All rights reserved. See copyright document for more details.
// This function serves as a REST API to access session history information.

if (file_exists(__DIR__ . '/../../config/db_name.php')) {
    include __DIR__ . '/../../config/db_name.php';
}
require_once('../http_session_functions.php');
require_once('../../db_exceptions.php');
require_once('../db_support_functions.php');
require_once('../format_functions.php');
require_once('../../data_functions.php');
require_once('./volunteer_job_model.php');
require_once('../authentication.php');

start_session_if_necessary();
$db = connect_to_db(true);
date_default_timezone_set(PHP_DEFAULT_TIMEZONE);
$authentication = new Authentication();
try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && $authentication->isLoggedIn()) {
        $jobs = VolunteerJob::findAll($db);
        $result = [];
        foreach ($jobs as $j) {
            $result[] = $j->asArray();
        }

        header('Content-type: application/json; charset=utf-8');
        $json_string = json_encode(array("jobs" => $result));
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
