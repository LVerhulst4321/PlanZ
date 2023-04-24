<?php
// Copyright (c) 2022 BC Holmes. All rights reserved. See copyright document for more details.
// This function serves as a REST API to access volunteer shift assignments (getting, creating and deleting)

if (file_exists(__DIR__ . '/../../config/db_name.php')) {
    include __DIR__ . '/../../config/db_name.php';
}
require_once(__DIR__ . '/../con_info.php');
require_once(__DIR__ . '/../http_session_functions.php');
require_once(__DIR__ . '/../../db_exceptions.php');
require_once(__DIR__ . '/../db_support_functions.php');
require_once(__DIR__ . '/../format_functions.php');
require_once(__DIR__ . '/../../data_functions.php');
require_once(__DIR__ . '/volunteer_job_model.php');
require_once(__DIR__ . '/volunteer_shift_model.php');
require_once(__DIR__ . '/../../con_data.php');
require_once(__DIR__ . '/../authentication.php');

function is_input_data_valid($db, $json) {
    return array_key_exists("shiftId", $json) && VolunteerShift::exists($db, $json['shiftId'])
        && array_key_exists("badgeId", $json);
}

start_session_if_necessary();
$db = connect_to_db(true);
$authentication = new Authentication();
try {

    $conInfo = ConInfo::findCurrentCon($db);
    if ($conInfo == null) {
        http_response_code(409);

    } else if ($_SERVER['REQUEST_METHOD'] === 'DELETE' && $authentication->isVolunteerSetUpAllowed()) {
        $json_string = file_get_contents('php://input');
        $json = json_decode($json_string, true);

        if (is_input_data_valid($db, $json)) {
            VolunteerShift::deleteAssignment($db, $json["badgeId"], $json["shiftId"]);
            http_response_code(204);
        } else {
            http_response_code(400);
        }

    } else if ($_SERVER['REQUEST_METHOD'] === 'POST' && $authentication->isVolunteerSetUpAllowed()) {
        $json_string = file_get_contents('php://input');
        $json = json_decode($json_string, true);

        if (is_input_data_valid($db, $json)) {
            try {
                VolunteerShift::createAssignment($db, $json["badgeId"], $json["shiftId"]);
                http_response_code(201);
            } catch (DatabaseDuplicateKeyException $e) {
                http_response_code(409);
            }
        } else {
            http_response_code(400);
        }

    } else if ($_SERVER['REQUEST_METHOD'] === 'DELETE' || $_SERVER['REQUEST_METHOD'] === 'POST') {
        http_response_code(401);
    } else {
        http_response_code(405);
    }

} finally {
    $db->close();
}
?>
