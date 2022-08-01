<?php
//	Copyright (c) 2011-2020 Peter Olszowka. All rights reserved. See copyright document for more details.
global $headerErrorMessage, $link, $linki, $title;
require_once('CommonCode.php');
require_once('login_functions.php');
require_once('./db_exceptions.php');
require_once('./api/admin/module_model.php');
$userIdPrompt = get_user_id_prompt();
if (!isset($_SESSION['badgeid'])) {
    $title = "Submit Password";
    $email = mb_strtolower(trim(getString('badgeid')), "UTF-8");
    $password = getString('passwd');
    $query_param_arr = array($email);
    $query = "SELECT password, data_retention, badgeid FROM Participants WHERE badgeid = ?;";
    $query_definition = 's';
    if (is_email_login_supported()) {
        $query = <<<EOD
SELECT 
       P.password, P.data_retention, P.badgeid, C.badgename, P.pubsname 
  FROM 
       Participants P 
  JOIN CongoDump C USING (badgeid)
 WHERE 
        lower(C.email) = ?;
EOD;
        $query_param_arr = array($email);
        $query_definition = 's';
    }
    if (!$result = mysqli_query_with_prepare_and_exit_on_error($query, $query_definition, $query_param_arr)) {
        exit(); // Should have exited already
    }
    if (mysqli_num_rows($result) != 1) {
        error_log("Number of records found for email address '$email' is: " . mysqli_num_rows($result));
        $headerErrorMessage = "Incorrect $userIdPrompt or password.";
        require('login.php');
        exit(0);
    }
    $dbobject = mysqli_fetch_object($result);
    mysqli_free_result($result);
    $query_param_arr = array($dbobject->badgeid);
    $badgeid = $dbobject->badgeid;
    $dbpassword = $dbobject->password;
    $_SESSION['data_consent'] = $dbobject->data_retention;
    if (!password_verify($password, $dbpassword)) {
        error_log("Password provided for email address '$email' is not valid");
        $headerErrorMessage = "Incorrect $userIdPrompt or password.";
        require('login.php');
        exit(0);
    }
    $_SESSION['badgename'] = $dbobject->badgename;
    $pubsname = $dbobject->pubsname;
    if ($pubsname != "" && $pubsname != null) {
        $_SESSION['badgename'] = $pubsname;
    }
    $_SESSION['badgeid'] = $badgeid;
    $_SESSION['hashedPassword'] = $dbpassword;
    set_permission_set($badgeid, $linki);
    set_modules($linki);
    set_login_time($badgeid);
} else {
    $badgeid = $_SESSION['badgeid'];
}
$message2 = "";
if (may_I('Staff')) {
    require('StaffPage.php');
} elseif (may_I('Participant')) {
    if (!$participant_array = retrieveFullParticipant($badgeid)) {
        $message_error = $message2 . "<br />Error retrieving data from DB.  No further execution possible.";
        RenderError($message_error);
    } else {
        require('renderWelcome.php');
    }
} elseif (may_I('public_login')) {
    require('renderBrainstormWelcome.php');
} else {
    unset($_SESSION['badgeid']);
    $message_error = "There is a problem with your $userIdPrompt's permission configuration:  It doesn't have ";
    $message_error .= "permission to access any welcome page.  Please contact Con staff.";
    RenderError($message_error);
}
exit();
?>
