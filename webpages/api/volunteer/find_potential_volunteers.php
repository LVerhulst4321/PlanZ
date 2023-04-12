<?php
// Copyright (c) 2023 BC Holmes. All rights reserved. See copyright document for more details.
// This function serves as a REST API to look up potential volunteers

if (file_exists(__DIR__ . '/../../config/db_name.php')) {
    include __DIR__ . '/../../config/db_name.php';
}

require_once(__DIR__ . '/../http_session_functions.php');
require_once(__DIR__ . '/../participant_assignment_model.php');
require_once(__DIR__ . '/../../db_exceptions.php');
require_once(__DIR__ . '/../db_support_functions.php');
require_once(__DIR__ . '/../format_functions.php');
require_once(__DIR__ . '/../../data_functions.php');
require_once(__DIR__ . '/../authentication.php');
require_once(__DIR__ . '/../simple_participant_search_result_model.php');

function findPotentialVolunteers($db, $shiftId, $q) {
    $lowerQueryString = '%' . mb_strtolower($q) . '%';
    $query = <<<EOD
    SELECT
        P.badgeid,
        P.pubsname,
        CD.badgename,
        CD.firstname,
        CD.lastname,
        CD.regtype,
        P.approvedphotofilename
    FROM Participants P
    JOIN CongoDump CD ON CD.badgeid = P.badgeid
    WHERE P.badgeid NOT IN (
            select badgeid from participant_has_volunteer_shift VS WHERE VS.volunteer_shift_id = ?)
      AND (P.sortedpubsname like ? OR lower(CD.badgename) like ? OR lower(CD.firstname) like ? OR lower(CD.lastname) like ?)
      AND CD.regtype IS NOT NULL
    ORDER BY badgename;
EOD;

    $stmt = mysqli_prepare($db, $query);
    mysqli_stmt_bind_param($stmt, "issss", $shiftId, $lowerQueryString, $lowerQueryString, $lowerQueryString, $lowerQueryString);
    $results = [];
    if (mysqli_stmt_execute($stmt)) {
        $resultSet = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_object($resultSet)) {
            $name = PersonName::from($row);
            $participant = new SimpleParticipantSearchResult();
            $participant->name = $name;
            $participant->badgeId = $row->badgeid;
            $participant->registered = ($row->regtype != null && $row->regtype != "");
            if ($row->approvedphotofilename) {
                $participant->avatarSrc = PHOTO_PUBLIC_DIRECTORY . '/' . $row->approvedphotofilename;
            } else {
                $participant->avatarSrc = PHOTO_PUBLIC_DIRECTORY . '/' . PHOTO_DEFAULT_IMAGE;
            }
            $results[] = $participant;
        }
        mysqli_stmt_close($stmt);
        return $results;
    } else {
        throw new DatabaseSqlException("Query could not be executed: $query");
    }
}

start_session_if_necessary();
$db = connect_to_db(true);
$authentication = new Authentication();
try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && $authentication->isVolunteerSetUpAllowed() && array_key_exists('shiftId', $_REQUEST)
            && array_key_exists('q', $_REQUEST)) {

        $shiftId = $_REQUEST['shiftId'];
        $query = $_REQUEST['q'];

        $candidates = findPotentialVolunteers($db, $shiftId, $query);

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

?>