<?php
//	Copyright (c) 2011-2017 The Zambia Group. All rights reserved. See copyright document for more details.

require_once('PartCommonCode.php');
require_once('login_functions.php');
require_once('data_functions.php');

if (is_logged_in()) {
    if (may_I('Staff')) {
        header('Location: ./StaffPage.php');
    } elseif (may_I('Participant')) {
        if (!$participant_array = retrieveFullParticipant($badgeid)) {
            $message_error = $message2 . "<br />Error retrieving data from DB.  No further execution possible.";
            RenderError($message_error);
        } else {
            header('Location: ./welcome.php');
        }
    } else {
        unset($_SESSION['badgeid']);
        $message_error = "There is a problem with your $userIdPrompt's permission configuration:  It doesn't have ";
        $message_error .= "permission to access any welcome page.  Please contact Con staff.";
        RenderError($message_error);
    }
} else {
    header('Location: ./login.php');
}    
?>
