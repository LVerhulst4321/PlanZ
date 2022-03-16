<?php
// Created by Peter Olszowka on 2020-04-21;
// Copyright (c) 2021 The Peter Olszowka. All rights reserved. See copyright document for more details.

global $linki, $title;
$title = "Submit Reset Password";
require ('PartCommonCode.php');
require_once('login_functions.php');
require_once('external/swiftmailer-5.4.8/lib/swift_required.php');
require_once('email_functions.php');

function send_password_was_reset_email($name, $email_address) {

    $programming = PROGRAM_EMAIL;
    if (!$name) {
        $name = $email_address;
    }

    $email_body = <<< EOD
        <html><body>
        <p>Hi $name,</p>

        <p>We just wanted to drop you a note to let you know that your password was reset at your request.</p>

        <p>If you have any questions, please contact <a href="mailto:$programming">Programming</a>.

        <p>Thanks,<br />
        Your Friendly Automated Email System!
        </p></body></html>
EOD;

    $text_body = <<< EOD
Hi $name,

We just wanted to drop you a note to let you know that your password was reset at your request.

If you have any questions, please contact Programming at $programming.

Thanks,
Your Friendly Automated Email System!
EOD;

    $con_name = CON_NAME;
    send_email_with_plain_text($text_body, $email_body, "Your $con_name password has been reset", [$email_address => $name]);
}

function get_badge_name($firstname, $lastname, $badgename) {
    if ($badgename) {
        return $badgename;
    } else {
        return $firstname . ' ' . $lastname;
    }
}

if (!defined('RESET_PASSWORD_SELF') || RESET_PASSWORD_SELF !== true) {
    http_response_code(403); // forbidden
    participant_header($title, true, 'Login', true);
    echo "<p class='alert alert-error vert-sep-above'>You have reached this page in error.</p>";
    participant_footer();
    exit;
}
$control = getString('control');
$controliv = getString('controliv');
$password = getString('password');
$cpassword = getString('cpassword');
$controlParams = interpretControlString($control, $controliv);
if (!$controlParams || empty($controlParams['selector']) || empty($controlParams['validator']) || empty($controlParams['badgeid'])) {
    participant_header($title, true, 'Login');
    echo "<p class='alert alert-error vert-sep-above'>Reset password form was missing required parameters.</p>";
    participant_footer();
    exit;
}
$selectorSQL = mysqli_real_escape_string($linki, $controlParams['selector']);
$query = <<<EOD
SELECT
        PPRR.badgeidentered, PPRR.token, P.pubsname, CD.badgename, CD.firstname, CD.lastname, CD.email
    FROM
             ParticipantPasswordResetRequests PPRR
        JOIN Participants P ON PPRR.badgeidentered = P.badgeid
        JOIN CongoDump CD USING (badgeid)
    WHERE
            selector = '$selectorSQL'
        AND cancelled = 0
        AND NOW() < expirationdatetime;
EOD;
if (!$result = mysqli_query_exit_on_error($query)) {
    exit;
}
if (mysqli_num_rows($result) !== 1) {
    participant_header($title, true, 'Login');
    echo "<p class='alert alert-error vert-sep-above'>Authentication error resetting password.</p>";
    participant_footer();
    exit;
}
list($badgeid, $token, $pubsname, $badgename, $firstname, $lastname, $email) = mysqli_fetch_array($result);
mysqli_free_result($result);
$calc = hash('sha256', hex2bin($controlParams['validator']));
if (!hash_equals($token, $calc) || $controlParams['badgeid'] !== $badgeid) {
    participant_header($title, true, 'Login');
    echo "<p class='alert alert-error vert-sep-above'>Authentication error resetting password.</p>";
    participant_footer();
    exit;
}
if (empty($password) || $password !== $cpassword) {
    participant_header($title, true, 'Login');
    $controlParams = array(
        "selector" => $selector,
        "validator" => $validator,
        "badgeid" => $badgeid
    );
    $controlArray = generateControlString($controlParams);
    if (!empty($badgename)) {
        $username = $badgename;
    } elseif (!empty($pubsname)) {
        $username = $pubsname;
    } else {
        $comboname = "$firstname $lastname";
        if (!empty($comboname)) {
            $username = $comboname;
        } else {
            $username = "";
        }
    }
    $params = array(
        "control" => $controlArray['control'],
        "controliv" => $controlArray['controliv'],
        "badgeid" => $badgeid,
        "user_name" => $username,
        "error_message" => "Passwords do not match or are blank.  Try again."
    );
    RenderXSLT('ForgotPasswordResetForm.xsl', $params);
    participant_footer();
}
$badgeidSQL = mysqli_real_escape_string($linki, $badgeid);
$query = <<<EOD
UPDATE ParticipantPasswordResetRequests
    SET cancelled = 1
    WHERE badgeidentered = '$badgeidSQL';
EOD;
if (!$result = mysqli_query_exit_on_error($query)) {
    exit;
}
$passwordHash = password_hash($password, PASSWORD_DEFAULT);
$query = <<<EOD
UPDATE Participants
    SET password = '$passwordHash'
    WHERE badgeid = '$badgeidSQL';
EOD;
if (!$result = mysqli_query_exit_on_error($query)) {
    exit;
}

send_password_was_reset_email(get_badge_name($firstname, $lastname, $badgename), $email);

header('Location: ./');