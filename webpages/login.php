<?php
//	Copyright (c) 2011-2020 Peter Olszowka. All rights reserved. See copyright document for more details.
global $title, $headerErrorMessage;
require_once('CommonCode.php');
require_once('ParticipantHeader.php');
require_once('ParticipantFooter.php');

$title="Login";

// Silent OIDC sign-in: send a visitor who isn't signed in to the provider once per browser session
// with prompt=none, so anyone already signed in there comes straight back signed in. Not after an
// error, not for non-GET requests, and not after the user logged out of PlanZ in this browser.
if (defined('OIDC_SILENT_SIGNIN') && OIDC_SILENT_SIGNIN === true && is_oidc_login_enabled()
        && !isset($_SESSION['badgeid']) && empty($_SESSION['oidc_silent_tried'])
        && empty($headerErrorMessage) && empty($_COOKIE['planz_logged_out'])
        && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET' && !headers_sent()) {
    header('Location: ' . BASE_PATH . 'oidc_login.php?silent=1');
    exit();
}

participant_header($title, false, 'Login', true);

if (SHOW_BRAINSTORM_LOGIN_HINT === TRUE && is_password_login_enabled()) {
?>

	<p id="brainstorm-login-hint"><span style="font-weight:bold">Brainstorm</span> users: if you want to submit ideas for panels, please enter "brainstorm" for your Badge ID
	and use the last name of the author of the Foundation series as your password (in all lowercase). </p>
<?php
    }
participant_footer(); ?>
