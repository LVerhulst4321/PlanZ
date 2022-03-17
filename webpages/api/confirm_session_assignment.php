<?php
// Created by BC Holmes on 2022-03-16.

if (!include ('../../db_name.php')) {
	include ('../../db_name.php');
}

require_once('./db_support_functions.php');
require_once('./http_session_functions.php');

function update_participant_confirmation($db, $sessionId, $participantSessionId, $badgeId, $confirmValue) {
    $query = <<<EOD
    UPDATE ParticipantOnSession
      SET confirmed = ?
    WHERE participantonsessionid = ?
      AND sessionid = ?
      AND badgeid = ?;
 EOD;

    $stmt = mysqli_prepare($db, $query);
    mysqli_stmt_bind_param($stmt, "siis", $confirmValue, $participantSessionId, $sessionId, $badgeId);

    if ($stmt->execute()) {
        mysqli_stmt_close($stmt);
    } else {
        throw new DatabaseSqlException("The Update could not be processed: $query --> " . mysqli_error($db));
    }
}

function update_participant_confirmation_notes($db, $sessionId, $participantSessionId, $badgeId, $notes) {
    $query = <<<EOD
    UPDATE ParticipantOnSession
      SET notes = ?
    WHERE participantonsessionid = ?
      AND sessionid = ?
      AND badgeid = ?;
 EOD;

    $stmt = mysqli_prepare($db, $query);
    mysqli_stmt_bind_param($stmt, "siis", $notes, $participantSessionId, $sessionId, $badgeId);

    if ($stmt->execute()) {
        mysqli_stmt_close($stmt);
    } else {
        throw new DatabaseSqlException("The Update could not be processed: $query --> " . mysqli_error($db));
    }
}


start_session_if_necessary();

$db = connect_to_db();
try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isLoggedIn()) {

        $json = file_get_contents('php://input');
        $data = json_decode($json, true);

        $badgeId = getLoggedInUserBadgeId();
        if ($data && array_key_exists('sessionId', $data) && array_key_exists('participantSessionId', $data)
             && array_key_exists('value', $data)) {

            update_participant_confirmation($db, $data['sessionId'], $data['participantSessionId'], $badgeId, $data['value']);
            http_response_code(204);
        } else if ($data && array_key_exists('sessionId', $data) && array_key_exists('participantSessionId', $data)
            && array_key_exists('notes', $data)) {

            update_participant_confirmation_notes($db, $data['sessionId'], $data['participantSessionId'], $badgeId, $data['notes']);
            http_response_code(204);
        } else {
            http_response_code(400); // badly-formatted request
        }

    } else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        http_response_code(401); // not authenticated
    } else {
        http_response_code(405); // method not allowed
    }
} finally {
    $db->close();
}
?>