<?php
// Copyright (c) 2021 BC Holmes. All rights reserved. See copyright document for more details.
// This function provides support for mobile apps such as WisSched and FogGuide

if (!include ('../config/db_name.php')) {
    include ('../config/db_name.php');
}

require_once('../db_exceptions.php');
require_once("db_support_functions.php");
require_once("jwt_functions.php");
require_once("participant_functions.php");

// the standard db_functions file makes certain assumptions about the end-client being
// HTML (to which it renders error pages), and those assumptions aren't good 
// in a REST/JSON world.
function resolve_login($userid, $password) {
    $db = connect_to_db();
    try {
        $query = <<<EOD
 SELECT 
        P.password, P.data_retention, P.badgeid, C.firstname, C.lastname, C.badgename, C.regtype 
   FROM 
        Participants P 
   JOIN CongoDump C USING (badgeid)
  WHERE 
         P.badgeid = ?
      OR 
         C.email = ?;
EOD;

        $stmt = mysqli_prepare($db, $query);
        mysqli_stmt_bind_param($stmt, "ss", $userid, $userid);
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            if (mysqli_num_rows($result) == 1) {
                $dbobject = mysqli_fetch_object($result);
                mysqli_stmt_close($stmt);
                if (password_verify($password, $dbobject->password)) {
                    return jwt_create_token($dbobject->badgeid, get_name($dbobject), $dbobject->regtype == null ? false : true);
                } else {
                    return false;
                }
            } else {
                return false;
            }
        } else {
            return false;
        }
    } finally {
        $db->close();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    $userid = $data['userid'];
    $password = $data['password'];

    $loginResult = resolve_login($userid, $password);

    if ($loginResult) {
        header('Content-type: application/json');
        header("Authorization: Bearer ".$loginResult);
        $result = array( "success" => true, "message" => "I like you. I really like you." );
        echo json_encode($result);
            
    } else {
        $result = array( "success" => false, "message" => "You're not of the body!" );
        http_response_code(401);
        echo "\n\n";
        echo json_encode($result);
    }
} else {
    http_response_code(405);
}

?>