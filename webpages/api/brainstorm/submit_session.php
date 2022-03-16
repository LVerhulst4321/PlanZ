<?php

if (!include ('../../../db_name.php')) {
	include ('../../../db_name.php');
}
require_once('../../external/swiftmailer-5.4.8/lib/swift_required.php');
require_once('../db_support_functions.php');
require_once('../jwt_functions.php');
require_once('../../email_functions.php');
require_once('../participant_functions.php');

function find_select_dropdown_by_name($db, $table, $idcolumn, $keycolumnname, $key) {

    $query = <<<EOD
 SELECT $idcolumn as id FROM $table WHERE $keycolumnname = ?;
 EOD;

    $stmt = mysqli_prepare($db, $query);
    mysqli_stmt_bind_param($stmt, "s", $key);
    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        if (mysqli_num_rows($result) == 1) {
            $dbobject = mysqli_fetch_object($result);
            mysqli_stmt_close($stmt);
            return $dbobject->id;
        } else {
            throw new DatabaseSqlException($query);
        }
    } else {
        throw new DatabaseSqlException($query);
    }
}

function find_division($db, $divisionId) {

    $query = <<<EOD
 SELECT email_address, divisionname FROM Divisions WHERE divisionid = ?;
 EOD;

    $stmt = mysqli_prepare($db, $query);
    mysqli_stmt_bind_param($stmt, "i", $divisionId);
    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        if (mysqli_num_rows($result) == 1) {
            $dbobject = mysqli_fetch_object($result);
            mysqli_stmt_close($stmt);
            return $dbobject;
        } else {
            throw new DatabaseSqlException($query);
        }
    } else {
        throw new DatabaseSqlException($query);
    }
}

function write_session_to_database($db, $json, $jwt) {

    $badgeid = jwt_extract_badgeid($jwt);
    $email = array_key_first(get_email_address_for_badgeid($db, $badgeid));
    $name = get_name_for_badgeid($db, $badgeid);

    mysqli_begin_transaction($db);
    try {
        $query = <<<EOD
    INSERT INTO Sessions 
            (title, progguiddesc, servicenotes, persppartinfo,
            divisionid, statusid, kidscatid, trackid, typeid, pubstatusid, roomsetid, duration, pocketprogtext, notesforprog)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?);
   EOD;

        $stmt = mysqli_prepare($db, $query);
        mysqli_stmt_bind_param($stmt, "ssssiiiiiiisss", $json['title'], $json['progguiddesc'], 
            $json['servicenotes'], $json['persppartinfo'], $json['division'], $json['statusid'], 
            $json['kidscatid'], $json['track'], $json['typeid'], 
            $json['pubstatusid'], $json['roomsetid'], $json['duration'], 
            $json['pocketprogtext'], $json['notesforprog']);

        if ($stmt->execute()) {
            mysqli_stmt_close($stmt);
        } else {
            throw new DatabaseSqlException($query);
        }     

        $sessionId = $db->insert_id;
        $default_description = 'Session suggested (Brainstorm).';
        $editCode = 1;
        $query = <<<EOD
    INSERT INTO SessionEditHistory 
            (sessionid, badgeid, name, email_address,
            sessioneditcode, statusid, editdescription)
    VALUES (?, ?, ?, ?, ?, ?, ?);
   EOD;

        $stmt = mysqli_prepare($db, $query);
        mysqli_stmt_bind_param($stmt, "isssiis", $sessionId, $badgeid, 
            $name, $email, $editCode, $json['statusid'], $default_description);

        if ($stmt->execute()) {
            mysqli_stmt_close($stmt);
        } else {
            throw new DatabaseSqlException($query);
        }
        mysqli_commit($db);     
    } catch (Exception $e) {
        mysqli_rollback($db);
        throw $e;
    }
}


function set_brainstorm_default_values($db, $json) {

    $json['statusid'] = find_select_dropdown_by_name($db, 'SessionStatuses', 'statusid', 'statusname', 'Brainstorm');
    $json['kidscatid'] = find_select_dropdown_by_name($db, 'KidsCategories', 'kidscatid', 'kidscatname', 'Welcome');
    $json['typeid'] = find_select_dropdown_by_name($db, 'Types', 'typeid', 'typename', 'I do not know');
    $json['pubstatusid'] = find_select_dropdown_by_name($db, 'PubStatuses', 'pubstatusid', 'pubstatusname', 'Public');
    $json['roomsetid'] = find_select_dropdown_by_name($db, 'RoomSets', 'roomsetid', 'roomsetname', 'Panel');
    $json['duration'] = DEFAULT_DURATION;
    return $json;
}

function get_email_address_for_badgeid($db, $badgeid) {
    $query = <<<EOD
        SELECT 
            P.badgeid, C.firstname, C.lastname, C.badgename, C.email 
        FROM 
            Participants P 
        JOIN CongoDump C USING (badgeid)
        WHERE 
            P.badgeid = ?;
    EOD;
    $stmt = mysqli_prepare($db, $query);
    mysqli_stmt_bind_param($stmt, "s", $badgeid);
    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        if (mysqli_num_rows($result) == 1) {
            $dbobject = mysqli_fetch_object($result);
            mysqli_stmt_close($stmt);
            $name = get_name($dbobject);
            if (trim($name) === '') {
                $name = $dbobject->email;
            }
            return [$dbobject->email => $name];
        } else {
            throw new DatabaseSqlException($query);
        }
    } else {
        throw new DatabaseSqlException($query);
    }
}

function get_name_for_badgeid($db, $badgeid) {
    $query = <<<EOD
        SELECT 
            P.badgeid, C.firstname, C.lastname, C.badgename 
        FROM 
            Participants P 
        JOIN CongoDump C USING (badgeid)
        WHERE 
            P.badgeid = ?;
    EOD;
    $stmt = mysqli_prepare($db, $query);
    mysqli_stmt_bind_param($stmt, "s", $badgeid);
    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        if (mysqli_num_rows($result) == 1) {
            $dbobject = mysqli_fetch_object($result);
            mysqli_stmt_close($stmt);
            return get_name($dbobject);
        } else {
            throw new DatabaseSqlException($query);
        }
    } else {
        throw new DatabaseSqlException($query);
    }
}

function send_confirmation_email($db, $json, $jwt, $division) {
    $badgeid = jwt_extract_badgeid($jwt);
    $email = get_email_address_for_badgeid($db, $badgeid);
    $name = get_name_for_badgeid($db, $badgeid);

    $programmingEmail = isset($division->email_address) && $division->email_address !== '' ? $division->email_address : PROGRAM_EMAIL;
    $programmingName = isset($division->divisionname) && $division->divisionname !== '' ? $division->divisionname : 'Programming';
    $title = strip_tags($json['title']);
    $description = strip_tags($json['progguiddesc']);
    $emailBody = <<<EOD
    <p>Hi $name,</p>
    <p>We've received your programming item submission:</p>
    
    <p><b>Title:</b><br />$title</p>

    <p><b>Description</b>:<br />
    $description</p>

    <p>If you have any questions, or need some help with some part of this process,
    please contact <a href="mailto:$programmingEmail">$programmingName</a>.<p>

    <p>Thank you for submitting your session idea.</p>
    <p>
        Thanks!<br />
        The System That Sends the Emails!
    </p>
EOD;

    send_email($emailBody, "Session submission: $title", $email, [$programmingEmail => $programmingName]);
}

function is_valid($db, $json) {
    if (!array_key_exists('title', $json) || $json['title'] === '') {
        return false;
    } else if (!array_key_exists('progguiddesc', $json) || $json['progguiddesc'] === '') {
        $length = mb_strlen($json['progguiddesc'], "utf-8");
        $words = preg_split('/\s+/', $json['progguiddesc'], -1, PREG_SPLIT_NO_EMPTY);
        $wordCount = count($words);
        return $length <= 500 || $wordCount <= 100;
    } else {
        return true;
    }
}

$auth = jwt_from_header();
if ($_SERVER['REQUEST_METHOD'] === 'POST' && jwt_validate_token($auth, true)) {

    $body = file_get_contents('php://input');
    $json = json_decode($body, true);

    // validate input
    $db = connect_to_db();
    try {
        if (is_valid($db, $json)) {

            // write to database
            write_session_to_database($db, set_brainstorm_default_values($db, $json), $auth);

            $division = find_division($db, $json['division']);

            // send email
            send_confirmation_email($db, $json, $auth, $division);

            http_response_code(201);
        } else {
            http_response_code(400);
        }
    } finally {
        $db->close();
    }
} else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    http_response_code(401);
} else {
    http_response_code(405);
}

?>