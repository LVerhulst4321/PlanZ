<?php

require_once(__DIR__ . '/../../config/db_name.php');

require_once(__DIR__ . '/../http_session_functions.php');
require_once(__DIR__ . '/../authentication.php');
require_once(__DIR__ . '/../db_support_functions.php');


function count_session_numbers($db) {
    $query = <<<EOD
    SELECT count(sess.sessionid) as c
      FROM Sessions sess
      JOIN Schedule sch USING (sessionid)
      JOIN Rooms r ON (sch.roomid = r.roomid)
     WHERE sess.pubstatusid = 2
       AND sess.pubsno IS NOT NULL
       AND sess.pubsno != ''
EOD;

    $stmt = mysqli_prepare($db, $query);
    $count = 0;
    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_object($result)) {
            $count = $row->c;
        }
        mysqli_stmt_close($stmt);
        return $count;
    } else {
        throw new DatabaseSqlException("Query could not be executed: $query");
    }
}


function enumerate_sessions($db) {
    $query = <<<EOD
    SELECT sess.sessionid
      FROM Sessions sess
      JOIN Schedule sch USING (sessionid)
      JOIN Rooms r ON (sch.roomid = r.roomid)
     WHERE sess.pubstatusid = 2
     ORDER BY sch.starttime, r.display_order
EOD;

    $stmt = mysqli_prepare($db, $query);
    $sessionIds = array();
    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_object($result)) {
            $sessionIds[] = $row->sessionid;
        }
        mysqli_stmt_close($stmt);
    } else {
        throw new DatabaseSqlException("Query could not be executed: $query");
    }

    $query = <<<EOD
    UPDATE Sessions sess
       SET pubsno = NULL;
EOD;
    $stmt = mysqli_prepare($db, $query);
    if ($stmt->execute()) {
        mysqli_stmt_close($stmt);
    } else {
        throw new DatabaseSqlException("The Update could not be processed: $query --> " . mysqli_error($db));
    }

$query = <<<EOD
    UPDATE Sessions sess
       SET pubsno = ?
     WHERE sessionid = ?;
EOD;
    $stmt = mysqli_prepare($db, $query);
    $enum = 1;
    foreach ($sessionIds as $id) {
        mysqli_stmt_bind_param($stmt, "ii", $enum, $id);
        if ($stmt->execute()) {
            $enum += 1;
        } else {
            throw new DatabaseSqlException("The Update could not be processed: $query --> " . mysqli_error($db));
        }
    }
    mysqli_stmt_close($stmt);
}

start_session_if_necessary();
$authentication = new Authentication();
$db = connect_to_db();
try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $authentication->isProgrammingStaff()) {

        $db->begin_transaction();
        try {
            enumerate_sessions($db);
            $db->commit();
            http_response_code(201);
        } catch (Exception $e) {
            $db->rollback();
            throw $e;
        }
    } else if ($_SERVER['REQUEST_METHOD'] === 'GET' && $authentication->isProgrammingStaff()) {
        $result = array("count" => count_session_numbers($db));
        header('Content-type: application/json');
        $json_string = json_encode($result);
        echo $json_string;
    } else if ($_SERVER['REQUEST_METHOD'] === 'POST' || $_SERVER['REQUEST_METHOD'] === 'GET') {
        http_response_code(401); // not authenticated
    } else {
        http_response_code(405); // method not allowed
    }
} finally {
    $db->close();
}

?>