<?php
// Copyright (c) 2019-2022 Leane Verhulst. All rights reserved. See copyright document for more details.

global $linki, $participant, $message_error, $message2, $title;
$title = "Personal Details";
require ('PartCommonCode.php'); // initialize db; check login;
require_once('renderMyDetails.php');

if (!may_I('my_gen_int_write')) {
    $message = "Currently, you do not have write access to this page.\n";
    RenderError($message);
    exit();
}

$newrow = $_POST["newrow"];

$dayjob = getString("dayjob");
$accessibilityissues = getString("accessibilityissues");
$ethnicity = getString("ethnicity");
$gender = getString("gender");
$sexualorientation = getString("sexualorientation");
$agerangeid = getInt("agerangeid", 1);
$pronounid = getInt("pronounid", 1);
$pronounother = getString("pronounother");

$dayjobE = mysqli_real_escape_string($linki, $dayjob);
$accessibilityissuesE = mysqli_real_escape_string($linki, $accessibilityissues);
$ethnicityE = mysqli_real_escape_string($linki, $ethnicity);
$genderE = mysqli_real_escape_string($linki, $gender);
$sexualorientationE = mysqli_real_escape_string($linki, $sexualorientation);
$agerangeidE = $agerangeid;
$pronounidE = $pronounid;
$pronounotherE = mysqli_real_escape_string($linki, $pronounother);


$query = "REPLACE ParticipantDetails SET ";
$query .= "badgeid='$badgeid', ";
$query .= "dayjob=$dayjobE, ";
$query .= "accessibilityissues=$accessibilityissuesE, ";
$query .= "ethnicity=$ethnicityE, ";
$query .= "gender=$genderE, ";
$query .= "sexualorientation=$sexualorientationE, ";
$query .= "agerangeid=$agerangeidE, ";
$query .= "pronounid=$pronounidE, ";
$query .= "pronounother=$pronounotherE;";

if (!mysqli_query($linki, $query)) {
    $message = $query . "<BR>Error updating database.  Database not updated.";
    RenderError($message);
    exit();
}


$message = "Database updated successfully.";
unset($message_error);

$error = false;
renderMyDetails($title, $error, $message, $dayjob, $accessibilityissues, $ethnicity, $gender, $sexualorientation, $agerangeid, $pronounid, $pronounother);

participant_footer();

exit(0);
?>
