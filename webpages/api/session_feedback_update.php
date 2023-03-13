<?php
// Copyright (c) 2021 BC Holmes. All rights reserved. See copyright document for more details.
// This function finds the panel list that we want to solicit participant feedback for.

if (file_exists(__DIR__ . '/../config/db_name.php')) {
    include __DIR__ . '/../config/db_name.php';
}
require_once('../db_exceptions.php');
require_once('./db_support_functions.php');
require_once('../data_functions.php');


function choose_query_based_on_field($name) {

    if ($name === 'interest') {
        $query = <<<EOD
        INSERT  INTO ParticipantSessionInterest
                (badgeid, sessionid, rank)
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE rank = ?;
EOD;
        return $query;
    } else if ($name === 'moderate') {
        $query = <<<EOD
        INSERT  INTO ParticipantSessionInterest
                (badgeid, sessionid, willmoderate)
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE willmoderate = ?;
EOD;
        return $query;
    } else if ($name === 'attend') {
        $query = <<<EOD
        INSERT  INTO ParticipantSessionInterest
                (badgeid, sessionid, attend)
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE attend = ?;
EOD;
        return $query;
    } else if ($name === 'attend-type') {
        $query = <<<EOD
        INSERT  INTO ParticipantSessionInterest
                (badgeid, sessionid, attend_type)
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE attend_type = ?;
EOD;
        return $query;
    } else if ($name === 'comments') {
        $query = <<<EOD
        INSERT  INTO ParticipantSessionInterest
                (badgeid, sessionid, comments)
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE comments = ?;
EOD;
        return $query;
    } else {
        return "";
    }
}

function update_session_interest_table($db, $badgeid, $sessionid, $name, $value) {
    $query = choose_query_based_on_field($name);
    $stmt = mysqli_prepare($db, $query);
    if ($name === 'comments') {
        mysqli_stmt_bind_param($stmt, "siis", $badgeid, $sessionid, $value, $value);
    } else {
        mysqli_stmt_bind_param($stmt, "siii", $badgeid, $sessionid, $value, $value);
    }
    if ($stmt && $stmt->execute()) {
        mysqli_stmt_close($stmt);
    } else {
        throw new DatabaseSqlException("There was a problem with the insert: $query");
    }
}

session_start();
$db = connect_to_db();
try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['badgeid'])) {
        if (may_I('SessionFeedback')) {

            $json_string = file_get_contents('php://input');
            $json = json_decode($json_string, true);

            if (array_key_exists("sessionId", $json) && array_key_exists("name", $json) && array_key_exists("value", $json)) {
                $value = $json['value'];
                if ($json['name'] === 'moderate') {
                    $value = ($value) ? 1 : 0;
                }

                update_session_interest_table($db, $_SESSION['badgeid'], $json['sessionId'], $json['name'], $value);
                http_response_code(204);
            } else {
                http_response_code(400);
            }
        } else {
            http_response_code(403);
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
