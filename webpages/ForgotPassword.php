<?php
// Created by Peter Olszowka on 2020-04-19;
// Copyright (c) 2021 The Peter Olszowka. All rights reserved. See copyright document for more details.
global $title;
$title = "Forgot Password";
require ('PartCommonCode.php');
require_once('login_functions.php');
if (!defined('RESET_PASSWORD_SELF') || RESET_PASSWORD_SELF !== true) {
    http_response_code(403); // forbidden
    participant_header($title, true, 'Login', true);
    echo "<p class='alert alert-error vert-sep-above'>You have reached this page in error.</p>";
    participant_footer();
    exit;
}
participant_header($title, true, 'Login', true);
$params = array("USER_ID_PROMPT" => get_user_id_prompt(), "TURNSTILE_SITE_KEY" => TURNSTILE_SITE_KEY, "EMAIL_LOGIN_SUPPORT" => is_email_login_supported(), "PUBLIC_NEW_USER" => PUBLIC_NEW_USER);
RenderXSLT('ForgotPassword.xsl', $params);
participant_footer();
?>


