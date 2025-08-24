<?php
// Created by Peter Olszowka on 2020-04-19;
// Copyright (c) 2020 The Peter Olszowka. All rights reserved. See copyright document for more details.
global $linki, $title;
$title = "Send Reset Password Link";
require ('PartCommonCode.php');
require_once('email_functions.php');
require_once('external/swiftmailer-5.4.8/lib/swift_required.php');
require_once('login_functions.php');

use GuzzleHttp\Client;

function validateTurnstile($token, $secret, $remoteip = null) {
    $url = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

    $data = [
        'secret' => $secret,
        'response' => $token
    ];

    if ($remoteip) {
        $data['remoteip'] = $remoteip;
    }

    $options = [
        'http' => [
            'header' => "Content-type: application/x-www-form-urlencoded\r\n",
            'method' => 'POST',
            'content' => http_build_query($data)
        ]
    ];

    $context = stream_context_create($options);
    $response = file_get_contents($url, false, $context);

    if ($response === FALSE) {
        return ['success' => false, 'error-codes' => ['internal-error']];
    }

    return json_decode($response, true);

}

function send_multiple_records_with_same_email($email, $subjectLine) {
    $conName = CON_NAME;
    $emailBody = <<<EOD
    <html><body>
    <p>
        Hello $email,
    </p>
    <p>
        We received a request to reset your password for the programming/scheduling system for $conName.
        If you did not make this request, you can ignore this email.
    </p>
    <p>
        Awkwardly, it looks like we have more than one record in our database with the same email address,
        which makes resetting your password a bit trickier. But hang tight! We've cc'ed our support team to get involved.
    </p>
    <p>
        Thanks!
    </p></body></html>
EOD;

    $textBody = <<<EOD
        Hello $email,

        We received a request to reset your password for the programming/scheduling system for $conName.
        If you did not make this request, you can ignore this email.

        Awkwardly, it looks like we have more than one record in our database with the same email address,
        which makes resetting your password a bit trickier. But hang tight! We've cc'ed our support team to get involved.

        Thanks!
EOD;

    $cc = [ CON_SUPPORT_EMAIL => CON_NAME . " Support" ];

    send_email_with_plain_text($textBody, $emailBody, $subjectLine, [ $email => $email ], null, $cc);
}

function send_email_not_found_message($email, $subjectLine, $url) {
    $conName = CON_NAME;
    $link_lifetime = PASSWORD_RESET_LINK_TIMEOUT_DISPLAY;
    $urlLink = sprintf('<a href="%s">%s</a>', $url, $url);
    $emailBody = <<<EOD
    <html><body>
    <p>
        Hello $email,
    </p>
    <p>
        We received a request to reset your password for the programming/scheduling system for $conName.
        If you did not make this request, you can ignore this email.
    </p>
    <p>
        It looks like we do not have an account that corresponds with that email address. But &mdash; good
        news &mdash; you can use the following URL to create a new account:
    </p>
    <p>$urlLink</p>
    <p>
        The link is good for $link_lifetime from when you originally requested it. If it has expired just
        request another link.
    </p>
    <p>
        Thanks!<br />
        The System that Sends the Emails
    </p></body></html>
EOD;

    $textBody = <<<EOD
        Hello $email,

        We received a request to reset your password for the programming/scheduling system for $conName.
        If you did not make this request, you can ignore this email.

        It looks like we do not have an account that corresponds with that email address. But -- good
        news -- you can use the following URL to create a new account:

        $url

        The link is good for $link_lifetime from when you originally requested it. If it has expired just
        request another link.

        Thanks!
        The System that Sends the Emails
EOD;

    send_email_with_plain_text($textBody, $emailBody, $subjectLine, [ $email => $email ]);
}

function send_no_new_user_email($email, $subjectLine) {
    $conName = CON_NAME;
    $emailBody = <<<EOD
    <html><body>
    <p>
        Hello $email,
    </p>
    <p>
        We received a request to reset your password for the programming/scheduling system for $conName.
        If you did not make this request, you can ignore this email.
    </p>
    <p>
        It looks like we do not have an account that corresponds with that email address. Please contact
        $conName to set up an account.
    </p>
    <p>
        Thanks!<br />
        The System that Sends the Emails
    </p></body></html>
EOD;

    $textBody = <<<EOD
        Hello $email,

        We received a request to reset your password for the programming/scheduling system for $conName.
        If you did not make this request, you can ignore this email.

        It looks like we do not have an account that corresponds with that email address. Please contact
        $conName to set up an account.

        Thanks!
        The System that Sends the Emails
EOD;

    send_email_with_plain_text($textBody, $emailBody, $subjectLine, [ $email => $email ]);
}

function send_reset_password_email($firstname, $lastname, $badgename, $email, $subjectLine, $url) {
    $conName = CON_NAME;

    //Define body
    $urlLink = sprintf('<a href="%s">%s</a>', $url, $url);
    if (!empty($badgename)) {
        $username = $badgename;
    } elseif (!empty($pubsname)) {
        $username = $pubsname;
    } else {
        $comboname = "$firstname $lastname";
        if (!empty($comboname)) {
            $username = $comboname;
        } else {
            $username = "unknown";
        }
    }
    $link_lifetime = PASSWORD_RESET_LINK_TIMEOUT_DISPLAY;
    $emailBody = <<<EOD
    <html><body>
    <p>
        Hello $username,
    </p>
    <p>
        We received a request to reset your password for the programming/scheduling system for $conName.
        If you did not make this request, you can ignore this email.
    </p>
    <p>
        Here is your password reset link:
    </p>
    <p>
        $urlLink
    </p>
    <p>
        The link is good for $link_lifetime from when you originally requested it and can be used to change
        your password only once.  If it has expired just request another link.
    </p>
    <p>
        Thanks!
    </p></body></html>
EOD;

    $text_body = <<<EOD
    Hello $username,

    We received a request to reset your password for the
    programming/scheduling system for $conName. If you did
    not make this request, you can ignore this email.

    Here is your password reset link:

    $url

    The link is good for $link_lifetime from when you originally
    requested it and can be used to change your password only once.
    If it has expired just request another link.

    Thanks!
EOD;

    send_email_with_plain_text($text_body, $emailBody, $subjectLine, [ $email => $username ]);
}

function validate_input_params($secret_key, $token, $remote_ip, $title, $badgeid, $email) {
    if (RESET_PASSWORD_SELF !== true) {
        http_response_code(403); // forbidden
        participant_header($title, true, 'Login', true);
        echo "<p class='alert alert-danger mt-2'>You have reached this page in error.</p>";
        participant_footer();
        exit;
    }

    $validation = validateTurnstile($token, $secret_key, $remoteip);
    if (!$validation['success']) {
        participant_header($title, true, 'Login', true);
        echo "<p class='alert alert-danger mt-2'>Error with Cloudflare Turnstile.</p>";
        participant_footer();
        exit;
    }

    if ((empty($badgeid) || empty($email)) && !is_email_login_supported()) {
        $params = array("USER_ID_PROMPT" => get_user_id_prompt(),
            "TURNSTILE_SITE_KEY" => TURNSTILE_SITE_KEY,
            "EMAIL_LOGIN_SUPPORT" => is_email_login_supported(),
            "PUBLIC_NEW_USER" => PUBLIC_NEW_USER);
        $params["error_message"] = "Both ${params['USER_ID_PROMPT']} and email address are required.";
        participant_header($title, true, 'Login', true);
        RenderXSLT('ForgotPassword.xsl', $params);
        participant_footer();
        exit;
    } else if (empty($email) && is_email_login_supported()) {
        $params = array("USER_ID_PROMPT" => get_user_id_prompt(),
            "TURNSTILE_SITE_KEY" => TURNSTILE_SITE_KEY,
            "EMAIL_LOGIN_SUPPORT" => is_email_login_supported(),
            "PUBLIC_NEW_USER" => PUBLIC_NEW_USER);
        $params["error_message"] = "Email address is required.";
        participant_header($title, true, 'Login', true);
        RenderXSLT('ForgotPassword.xsl', $params);
        participant_footer();
        exit;
    }
}

function insert_reset_item($badgeidSQL, $emailSQL, $ipaddressSQL, $token, $selector) {
    // Token expiration
    $expires = new DateTime('NOW');
    $expires->add(new DateInterval(PASSWORD_RESET_LINK_TIMEOUT));
    $expirationSQL = date_format($expires,'Y-m-d H:i:s');
    $tokenSQL = hash('sha256', $token);
    $query = <<<EOD
    UPDATE ParticipantPasswordResetRequests
        SET cancelled = 1
        WHERE badgeidentered = '$badgeidSQL';
EOD;
    if (!$result = mysqli_query_exit_on_error($query)) {
        exit;
    }
    $query = <<<EOD
    INSERT INTO ParticipantPasswordResetRequests
        (badgeidentered, email, ipaddress, expirationdatetime, selector, token)
        VALUES ('$badgeidSQL', '$emailSQL', '$ipaddressSQL', '$expirationSQL', '$selector', '$tokenSQL');
EOD;
    if (!$result = mysqli_query_exit_on_error($query)) {
        exit;
    }
}

function insert_create_item($emailSQL, $ipaddressSQL, $token, $selector) {
    // Token expiration
    $expires = new DateTime('NOW');
    $expires->add(new DateInterval(PASSWORD_RESET_LINK_TIMEOUT));
    $expirationSQL = date_format($expires,'Y-m-d H:i:s');
    $tokenSQL = hash('sha256', $token);
    $query = <<<EOD
    INSERT INTO ParticipantPasswordResetRequests
        (badgeidentered, email, ipaddress, expirationdatetime, selector, token)
        VALUES ('', '$emailSQL', '$ipaddressSQL', '$expirationSQL', '$selector', '$tokenSQL');
EOD;
    if (!$result = mysqli_query_exit_on_error($query)) {
        exit;
    }
}


$secret_key = TURNSTILE_SECRET_KEY;
$token = $_POST['cf-turnstile-response'] ?? '';
$remoteip = $_SERVER['HTTP_CF_CONNECTING_IP'] ??
    $_SERVER['HTTP_X_FORWARDED_FOR'] ??
    $_SERVER['REMOTE_ADDR'];

$badgeid = getString('badgeid');
$email = getString('emailAddress');

validate_input_params($secret_key, $token, $remoteip, $title, $badgeid, $email);
participant_header($title, true, 'Login', true);

$conName = CON_NAME;
$subjectLine = "$conName Password Reset";
$fromAddress = PASSWORD_RESET_FROM_EMAIL;
$responseParams = array("subject_line" => $subjectLine, "from_address" => $fromAddress);

$badgeidSQL = mysqli_real_escape_string($linki, $badgeid);
$emailSQL = trim(mb_strtolower(mysqli_real_escape_string($linki, $email), 'UTF-8'));
$query = <<<EOD
SELECT P.pubsname, CD.badgename, CD.firstname, CD.lastname, P.badgeid
    FROM
             Participants P
        JOIN CongoDump CD USING (badgeid)
    WHERE
            P.badgeid = '$badgeidSQL'
        AND CD.email = '$emailSQL';
EOD;

if (is_email_login_supported()) {
    $badgeidSQL = '';
    $query = <<<EOD
SELECT P.pubsname, CD.badgename, CD.firstname, CD.lastname, P.badgeid
    FROM
             Participants P
        JOIN CongoDump CD USING (badgeid)
    WHERE
            LOWER(CD.email) = '$emailSQL';
EOD;
}

if (!$result = mysqli_query_exit_on_error($query)) {
    exit;
}

$userIP = $_SERVER['REMOTE_ADDR'];
$ipaddressSQL = mysqli_real_escape_string($linki, $userIP);
$selector = bin2hex(random_bytes(8));
// Create tokens
$token = random_bytes(32);

$record_count = mysqli_num_rows($result);
if (is_email_login_supported() && $record_count === 0 && PUBLIC_NEW_USER) {
    $url = sprintf('%sCreateNewAccountLink.php?%s', ROOT_URL, http_build_query([
        'selector' => $selector,
        'validator' => bin2hex($token)
    ]));

    insert_create_item($emailSQL, $ipaddressSQL, $token, $selector);
    send_email_not_found_message($email, $subjectLine, $url);

} else if (is_email_login_supported() && $record_count === 0 && !PUBLIC_NEW_USER) {
    // record a non-valid request to help track issues
    $query = <<<EOD
INSERT INTO ParticipantPasswordResetRequests
    (badgeidentered, email, ipaddress, cancelled, selector)
    VALUES ('$badgeidSQL', '$emailSQL', '$ipaddressSQL', 2, '$selector');
EOD;
    if (!$result = mysqli_query_exit_on_error($query)) {
        exit;
    }
    // tell user that email address can't be set up
    send_no_new_user_email($email, $subjectLine);

} else if ($record_count !== 1) {
    // record a non-valid request to help track issues
    $query = <<<EOD
INSERT INTO ParticipantPasswordResetRequests
    (badgeidentered, email, ipaddress, cancelled, selector)
    VALUES ('$badgeidSQL', '$emailSQL', '$ipaddressSQL', 2, '$selector');
EOD;
    if (!$result = mysqli_query_exit_on_error($query)) {
        exit;
    }
    // don't tell user anything went wrong -- just give regular response.
    if (is_email_login_supported() && $record_count > 1) {
        send_multiple_records_with_same_email($email, $subjectLine);
    }
    //RenderXSLT('ForgotPasswordResponse.xsl', $responseParams);
    //participant_footer();
    //exit;

} else {
    $url = sprintf('%sForgotPasswordLink.php?%s', ROOT_URL, http_build_query([
        'selector' => $selector,
        'validator' => bin2hex($token)
    ]));

    list($pubsname, $badgename, $firstname, $lastname, $badgeid) = mysqli_fetch_array($result);
    $badgeidSQL = mysqli_real_escape_string($linki, $badgeid);
    mysqli_free_result($result);

    insert_reset_item($badgeidSQL, $emailSQL, $ipaddressSQL, $token, $selector);
    send_reset_password_email($firstname, $lastname, $badgename, $email, $subjectLine, $url);
}

// regular response is name as error response above
RenderXSLT('ForgotPasswordResponse.xsl', $responseParams);
participant_footer();
?>
