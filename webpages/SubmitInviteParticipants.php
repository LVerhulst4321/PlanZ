<?php
//	Copyright (c) 2021 Peter Olszowka. All rights reserved. See copyright document for more details.
global $returnAjaxErrors, $return500errors;
$returnAjaxErrors = true;
$return500errors = true;
require_once('StaffCommonCode.php');
require_once('surveyFilterBuild.php');

/**
 * Check if participant already invited to item.
 * @param int $partbadgeid
 * @param int $sessionid
 * @return bool True if participant already on session.
 */
function check_already_invited($partbadgeid, $sessionid) {
    global $linki;

    $query = "SELECT 1 FROM ParticipantSessionInterest WHERE badgeid='$partbadgeid' AND sessionid=$sessionid;";
    $result = mysqli_query($linki, $query);
    $row = mysqli_fetch_assoc($result);
    if ($row) {
        return true;
    }
    return false;
}

/**
 * Insert to ParticipantSessionInterest table.
 * @param int $partbadgeid
 * @param int $sessionid
 * @return bool True if participant already on session.
 */
function add_participant_invite($partbadgeid, $sessionid) {
    global $linki;
        
    $query = "INSERT INTO ParticipantSessionInterest SET badgeid='$partbadgeid', ";
    $query .= "sessionid=$sessionid;";
    return mysqli_query($linki, $query);
}

/**
 * If participant and session valid, and if participant not already on session, invite them.
 */
function invite_participant() {
    global $linki;

    $partbadgeid = getString("selpart");
    if ($partbadgeid !== NULL)
        $partbadgeid = mysqli_real_escape_string($linki, $partbadgeid);
    else
        $partbadgeid = '';
    $sessionid = getInt("selsess", 0);
 
    if (($partbadgeid == '') || ($sessionid == 0)) {
        $message = "<p class=\"alert alert-error\">Database not updated. Select a participant and a session.</p>";
        $alerttype = "warning";
    } else {
        if (check_already_invited($partbadgeid, $sessionid)) {
            $message =  "<p>Database not updated. That participant was already invited to that session.</p>";
            $alerttype = "warning";
        } elseif (add_participant_invite($partbadgeid, $sessionid)) {
            $message =  "<p>Database successfully updated.</p>";
        } else {
            $message = $query . "<p>Database not updated.</p>";
            $alerttype = "danger";
        }
    }
    $json_return = array();
    $json_return["message"] = $message;
    $json_return["alerttype"] = $alerttype;
    echo json_encode($json_return) . "\n";
}

// Start here.  Should be AJAX requests only
global $returnAjaxErrors, $return500errors;
$returnAjaxErrors = true;
$return500errors = true;
$ajax_request_action = getString("ajax_request_action");
if ($ajax_request_action == "" || !isLoggedIn() || !may_I("Staff")) {
    exit();
}

switch ($ajax_request_action) {
    case "invite":
        invite_participant();
        break;
    default:
        $message_error = "Internal error.";
        RenderErrorAjax($message_error);
        exit();
}

?>
