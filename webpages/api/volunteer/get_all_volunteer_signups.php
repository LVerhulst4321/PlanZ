<?php
// Copyright (c) 2022 BC Holmes. All rights reserved. See copyright document for more details.
// This function serves as a REST API to access volunteer shifts

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

start_session_if_necessary();
$db = connect_to_db(true);
date_default_timezone_set(PHP_DEFAULT_TIMEZONE);
$authentication = new Authentication();
try {
    $conInfo = ConInfo::findCurrentCon($db);

    if ($conInfo == null) {
        http_response_code(409);
    } else if ($_SERVER['REQUEST_METHOD'] === 'GET' && $authentication->isVolunteerSetUpAllowed()) {
        $shifts = VolunteerShift::findAllAssignments($db, $conInfo, $conInfo);
        $result = [];
        foreach ($shifts as $s) {
            $result[] = $s->asArray();
        }

        header('Content-type: application/json; charset=utf-8');
        $json_string = json_encode(array("shifts" => $result, "context" => array("timezone" => PHP_DEFAULT_TIMEZONE, "days" => get_potential_days())));
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
