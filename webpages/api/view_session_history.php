<?php
// Copyright (c) 2022 BC Holmes. All rights reserved. See copyright document for more details.
// This function serves as a REST API to access session history information.

if (file_exists(__DIR__ . '/../config/db_name.php')) {
    include __DIR__ . '/../config/db_name.php';
}
require_once('./http_session_functions.php');
require_once('../db_exceptions.php');
require_once('./db_support_functions.php');
require_once('./format_functions.php');
require_once('../data_functions.php');
require_once('./authentication.php');

function get_session_edits($db, $sessionId) {
    $query = <<<EOD
SELECT
        SEH.badgeid,
        SEH.name,
        CD.badgename,
        SEH.editdescription,
        SEH.timestamp,
        DATE_FORMAT(SEH.timestamp, "%c/%e/%y %l:%i %p") AS tsformat,
        SEC.description AS codedescription,
        SS.statusname
    FROM
             SessionEditHistory SEH
        JOIN SessionEditCodes SEC USING (sessioneditcode)
        JOIN SessionStatuses SS USING (statusid)
        JOIN CongoDump CD USING (badgeid)
    WHERE
        SEH.sessionid=?;
EOD;

    $stmt = mysqli_prepare($db, $query);
    mysqli_stmt_bind_param($stmt, "i", $sessionId);
    $history = [];
    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_object($result)) {
            $record = [
                "badgeid" => $row->badgeid,
                "name" => $row->badgename,
                "description" => $row->editdescription,
                "timestamp" => date_format(convert_database_date_to_date($row->timestamp) , 'c'),
                "codedescription" => $row->codedescription,
                "status" => $row->statusname
            ];
            $history[] = $record;
        }
        mysqli_stmt_close($stmt);
        return $history;
    } else {
        throw new DatabaseSqlException("Query could not be executed: $query");
    }
}

function get_participant_edits($db, $sessionId) {
    $query = <<<EOD
    SELECT
        POSH.badgeid,
        COALESCE(POSH.moderator, 0) AS moderator,
        POSH.change_by_badgeid,
        POSH.change_ts,
        POSH.change_type,
        DATE_FORMAT(POSH.change_ts, "%c/%e/%y %l:%i %p") AS change_ts_format,
        PartOS.pubsname,
        CD.badgename,
        PartCR.pubsname AS crpubsname
    FROM
                  participant_on_session_history POSH
             JOIN Participants PartOS ON PartOS.badgeid = POSH.badgeid
             JOIN CongoDump CD ON CD.badgeid = POSH.badgeid
             JOIN Participants PartCR ON PartCR.badgeid = POSH.change_by_badgeid
    WHERE
        POSH.sessionid=?;
EOD;

    $stmt = mysqli_prepare($db, $query);
    mysqli_stmt_bind_param($stmt, "i", $sessionId);
    $history = [];
    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_object($result)) {
            $changeType = $row->change_type;
            $description = "Participant added: " . $row->badgename;
            if ($changeType == 'remove_assignment') {
                $description = "Participant removed: " . $row->badgename;
            } else if ($changeType == 'assign_moderator') {
                $description = "Participant assigned as moderator: " . $row->badgename;
            } else if ($changeType == 'remove_moderator') {
                $description = "Participant removed as moderator: " . $row->badgename;
            }
            $history[] = [
                "badgeid" => $row->change_by_badgeid,
                "name" => $row->crpubsname,
                "timestamp" => date_format(convert_database_date_to_date($row->change_ts) , 'c'),
                "codedescription" => $description
            ];
        }
        mysqli_stmt_close($stmt);
        return $history;
    } else {
        throw new DatabaseSqlException("Query could not be executed: $query");
    }
}

function sort_history_by_timestamp($a, $b) {
    return strcmp($a['timestamp'], $b['timestamp']);
}

start_session_if_necessary();
$db = connect_to_db(true);
$authentication = new Authentication();
try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && $authentication->isProgrammingStaff()) {
        if (array_key_exists("id", $_REQUEST)) {

            $sessionId = $_REQUEST['id'];
            $history = get_session_edits($db, $sessionId);
            $more_history = get_participant_edits($db, $sessionId);
            $both = array_merge($history, $more_history);

            usort($both, 'sort_history_by_timestamp');

            header('Content-type: application/json; charset=utf-8');
            $json_string = json_encode(array("history" => $both));
            echo $json_string;

        } else {
            http_response_code(400);
        }
    } else if ($_SERVER['REQUEST_METHOD'] === 'GET' && isLoggedIn()) {
        http_response_code(403);
    } else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        http_response_code(401);
    } else {
        http_response_code(405);
    }

} finally {
    $db->close();
}

?>
