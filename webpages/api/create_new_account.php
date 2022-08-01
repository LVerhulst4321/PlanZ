<?php
// Copyright (c) 2021 BC Holmes. All rights reserved. See copyright document for more details.
// This function finds the panel list that we want to solicit participant feedback for.

if (!include ('../config/db_name.php')) {
    include ('../config/db_name.php');
}
require_once('../external/swiftmailer-5.4.8/lib/swift_required.php');
require_once('../db_exceptions.php');
require_once('./db_support_functions.php');
require_once('../data_functions.php');
require_once('../email_functions.php');

function send_email_to_support($email, $badgeName, $badgeid) {

    $supportName = CON_NAME . " Support";
    $supportEmail = [ CON_SUPPORT_EMAIL => $supportName ];
    $emailBody = <<<EOD
    <p>Hi $supportName</p>
    <p>
        A user has created a new account in the programming system. We just thought you should
        know that because it could be legit, or it could be nasty hackers from Russia. 
    </p>
    <p>
        <b>Name: </b> $badgeName<br />
        <b>Email: </b> $email<br />
        <b>Id: </b> $badgeid
    </p>
    <p>
        Thanks!<br />
        The System That Sends the Emails!
    </p>
EOD;

    send_email($emailBody, "New User Created: $badgeName", $supportEmail);
}

function find_original_email_address($db, $selector, $validator) {

    if (DB_DEFAULT_TIMEZONE != "") {
        $query = "SET time_zone = '" . DB_DEFAULT_TIMEZONE . "';";
        if (!mysqli_query($db, $query)) {
            throw new DatabaseSqlException("Could not process timezone change: $query");
        }
    }

    $query=<<<EOD
    SELECT
            email
        FROM
            ParticipantPasswordResetRequests
        WHERE badgeidentered = ''
        AND selector = ?
        AND cancelled = 0
        AND NOW() < expirationdatetime;
EOD;
    $email = null;
    $stmt = mysqli_prepare($db, $query);
    mysqli_stmt_bind_param($stmt, "s", $selector);
    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);

        while ($row = mysqli_fetch_object($result)) {
            $email = $row->email;
        }
        $result->free_result();
        $stmt->close();
        
        return $email;
    } else {
        throw new DatabaseSqlException("The query could not be processed");
    }
}

function next_badgeid($db) {
    $query=<<<EOD
    SELECT
            MAX(CONVERT(badgeid, UNSIGNED)) M
        FROM
            Participants
        WHERE badgeid LIKE '
EOD;
    $query .=  "%'";
    $last_badgeid = "";

    $stmt = mysqli_prepare($db, $query);
    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);

        while ($row = mysqli_fetch_object($result)) {
            $last_badgeid = $row->M;
        }
        mysqli_free_result($result);
        if ($last_badgeid == "") {
            $last_badgeid = "1000";
        }
        $id = $last_badgeid;
        return strval(intval($id) + 1);
    } else {
        throw new DatabaseSqlException("The query could not be processed");
    }
}

function split_first_and_last_names($name) {
    $words = preg_split('/\s+/',  $name, -1, PREG_SPLIT_NO_EMPTY);

    if ($words == false) {
        return array("first_name" => '',
            "last_name" => $name);
    } else if (count($words) === 0) {
        return array("first_name" => '',
            "last_name" => '');
    } else if (count($words) === 1) {
        return array("first_name" => '',
            "last_name" => $name);
    } else {
        $last = $words[count($words) - 1];
        $first = "";
        for ($i = 0; $i < count($words) - 1; $i++) {
            if ($i > 0) {
                $first .= ' ';
            }
            $first .= $words[$i]; 
        }
        return array("first_name" => $first,
            "last_name" => $last);
    }
}

function update_reset_request($db, $selector) {
    $query = <<<EOD
    UPDATE ParticipantPasswordResetRequests
      SET cancelled = 1
        WHERE badgeidentered = ''
        AND selector = ?;
EOD;

    $stmt = mysqli_prepare($db, $query);
    mysqli_stmt_bind_param($stmt, "s", $selector);

    if ($stmt->execute()) {
        mysqli_stmt_close($stmt);
    } else {
        throw new DatabaseSqlException("The Insert could not be processed: $query");
    }
}

function create_new_participant($db, $badgeid, $pubsname, $email_address, $password) {
    $query = <<<EOD
    INSERT
        INTO `Participants` (badgeid, pubsname, password)
 VALUES 
        (?, ?, ?);
EOD;

    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = mysqli_prepare($db, $query);
    mysqli_stmt_bind_param($stmt, "sss", $badgeid, $pubsname, $passwordHash);

    if ($stmt->execute()) {
        mysqli_stmt_close($stmt);
    } else {
        throw new DatabaseSqlException("The Insert could not be processed: $query");
    }

    $name = split_first_and_last_names($pubsname);
    $query = <<<EOD
    INSERT
        INTO `CongoDump` (badgeid, badgename, `email`, firstname, lastname)
    VALUES 
        (?, ?, ?, ?, ?);
EOD;

    $stmt = mysqli_prepare($db, $query);
    mysqli_stmt_bind_param($stmt, "sssss", $badgeid, $pubsname, $email_address, $name['first_name'], $name['last_name']);
    if ($stmt->execute()) {
        mysqli_stmt_close($stmt);
    } else {
        throw new DatabaseSqlException("The Insert could not be processed: $query");
    }

    $query = <<<EOD
    INSERT INTO `UserHasPermissionRole` 
        (badgeid, permroleid)
    SELECT ?, permroleid
      FROM PermissionRoles
     WHERE permrolename = 'Program Participant';
EOD;

    $stmt = mysqli_prepare($db, $query);
    mysqli_stmt_bind_param($stmt, "s", $badgeid);
    if ($stmt->execute()) {
        mysqli_stmt_close($stmt);
    } else {
        throw new DatabaseSqlException("The Insert could not be processed: $query");
    }
}


$db = connect_to_db();
try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $json_string = file_get_contents('php://input');
        $json = json_decode($json_string, true);

        if (array_key_exists('control', $json) && array_key_exists('controliv', $json) 
            && array_key_exists('badgeName', $json) && array_key_exists('password', $json)) {

            $control = interpretControlString($json['control'], $json['controliv']);
            $email = $control ? find_original_email_address($db, $control['selector'], $control['validator']) : null;
            if ($email) {
                $badgeid = next_badgeid($db);
                $db->begin_transaction();
                try {
                    create_new_participant($db, $badgeid, $json['badgeName'], $email, $json['password']);
                    update_reset_request($db, $control['selector']);
                    $db->commit();
                    send_email_to_support($email, $json['badgeName'], $badgeid);
                    http_response_code(201);
                } catch (Exception $e) {
                    $db->rollback();
                    throw $e;
                }
            } else {
                http_response_code(400);
            }
        } else {
            http_response_code(400);
        }
    } else {
        http_response_code(405);
    }
} finally {
    $db->close();
}

?>