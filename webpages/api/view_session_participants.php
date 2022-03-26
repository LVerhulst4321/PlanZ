<?php
// Copyright (c) 2022 BC Holmes. All rights reserved. See copyright document for more details.
// This function serves as a REST API to access session history information.

if (!include ('../config/db_name.php')) {
    include ('../config/db_name.php');
}
require_once('./http_session_functions.php');
require_once('./db_support_functions.php');
require_once('./format_functions.php');
require_once('../data_functions.php');
require_once('../name.php');

function get_participant_assignments($db, $sessionId) {
    $query = <<<EOD
    SELECT
        POS.badgeid,
        COALESCE(POS.moderator, 0) AS moderator,
        P.pubsname,
        CD.badgename,
        CD.firstname,
        CD.lastname
    FROM
                  ParticipantOnSession POS
             JOIN Participants P ON P.badgeid = POS.badgeid
             JOIN CongoDump CD ON CD.badgeid = POS.badgeid
    WHERE
        POS.sessionid=?;
EOD;

    $stmt = mysqli_prepare($db, $query);
    mysqli_stmt_bind_param($stmt, "i", $sessionId);
    $assignments = [];
    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_object($result)) {
            $name = new PersonName();
            $name->firstName = $row->firstname;
            $name->lastName = $row->lastname;
            $name->badgeName = $row->badgename;
            $name->pubsName = $row->pubsname;
            $assignments[] = [ 
                "badgeid" => $row->badgeid,
                "moderator" => $row->moderator ? true : false,
                "name" => $name->getBadgeName()
            ];
        }
        mysqli_stmt_close($stmt);
        return $assignments;
    } else {
        throw new DatabaseSqlException("Query could not be executed: $query");
    }
}

start_session_if_necessary();
$db = connect_to_db(true);
try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && isProgrammingStaff()) {
        if (array_key_exists("id", $_REQUEST)) {

            $sessionId = $_REQUEST['id'];
            $assignments = get_participant_assignments($db, $sessionId);

            header('Content-type: application/json; charset=utf-8');
            $json_string = json_encode(array("assignments" => $assignments));
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