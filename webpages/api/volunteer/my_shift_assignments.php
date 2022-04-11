<?php
// Copyright (c) 2022 BC Holmes. All rights reserved. See copyright document for more details.
// This function serves as a REST API to access volunteer shift assignments (getting, creating and deleting)

if (!include ('../../config/db_name.php')) {
    include ('../../config/db_name.php');
}
require_once('../http_session_functions.php');
require_once('../db_support_functions.php');
require_once('../format_functions.php');
require_once('../../data_functions.php');
require_once('./volunteer_job_model.php');
require_once('./volunteer_shift_model.php');
require_once('../../con_data.php');

// include one day before the con and one day after
// because set-up and tear down sometimes happens on
// those days.
function get_potential_days() {
    $conData = ConData::fromEnvironmentDefinition();
    $days = [];
    for ($i = -1; $i <= $conData->numberOfDays; $i++) {
        $day = clone $conData->startDate;
        if ($i < 0) {
            $day->sub(new DateInterval('P'.abs($i).'D'));
        } else if ($i > 0) {
            $day->add(new DateInterval('P'.$i.'D'));
        }
        $days[] = $day->format('Y-m-d');
    }
    return $days;
}

function is_input_data_valid($db, $json) {
    return array_key_exists("shiftId", $json) && VolunteerShift::exists($db, $json['shiftId']);
}

start_session_if_necessary();
$db = connect_to_db(true);
try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && isLoggedIn()) {
        $badgeId = getBadgeId();
        $shifts = VolunteerShift::findAllAssignedToParticipant($db, $badgeId);
        $result = [];
        foreach ($shifts as $s) {
            $result[] = $s->asArray();
        }

        header('Content-type: application/json; charset=utf-8');
        $json_string = json_encode(array("shifts" => $result, "context" => array("timezone" => PHP_DEFAULT_TIMEZONE, "days" => get_potential_days())));
        echo $json_string;

    } else if ($_SERVER['REQUEST_METHOD'] === 'DELETE' && isLoggedIn()) {
        $badgeId = getBadgeId();
        $json_string = file_get_contents('php://input');
        $json = json_decode($json_string, true);
        
        if (is_input_data_valid($db, $json)) {
            VolunteerShift::deleteAssignment($db, $badgeId, $json["shiftId"]);
            http_response_code(204);
        } else {
            http_response_code(400);
        }

    } else if ($_SERVER['REQUEST_METHOD'] === 'POST' && isLoggedIn()) {
        $badgeId = getBadgeId();
        $json_string = file_get_contents('php://input');
        $json = json_decode($json_string, true);
        
        if (is_input_data_valid($db, $json)) {
            try {
                VolunteerShift::createAssignment($db, $badgeId, $json["shiftId"]);
                http_response_code(201);
            } catch (DatabaseDuplicateKeyException $e) {
                http_response_code(409);
            }
        } else {
            http_response_code(400);
        }

    } else if ($_SERVER['REQUEST_METHOD'] === 'GET' || $_SERVER['REQUEST_METHOD'] === 'DELETE' || $_SERVER['REQUEST_METHOD'] === 'POST') {
        http_response_code(401);
    } else {
        http_response_code(405);
    }

} finally {
    $db->close();
}
?>