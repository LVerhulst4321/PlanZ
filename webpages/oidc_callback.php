<?php
// Copyright (c) 2026 PlanZ contributors. All rights reserved. See copyright document for more details.
//
// The OpenID Connect redirect URI. The provider sends the browser back here with a code (or an
// error). We exchange the code, verify the ID token, read the member claims from userinfo, and
// either sign in as the only member, or send the user to choose which member to act as.
global $headerErrorMessage, $title, $linki;
require_once('CommonCode.php');
require_once('oidc_functions.php');
require_once('./api/admin/module_model.php');

function oidc_callback_fail(string $message): void {
    global $headerErrorMessage;
    unset($_SESSION['badgeid'], $_SESSION['auth_method'], $_SESSION['permission_set'],
        $_SESSION['oidc_members'], $_SESSION['oidc_entitlements'], $_SESSION['oidc_active_mid']);
    $_SESSION['oidc_silent_tried'] = true;
    $headerErrorMessage = $message;
    require('login.php');
    exit();
}

if (!is_oidc_login_enabled()) {
    header('Location: ./login.php');
    exit();
}

$config = oidc_config();
$pending = $_SESSION['oidc_pending'] ?? null;
unset($_SESSION['oidc_pending']); // single use, whatever happens next

$problems = oidc_config_problems($config);
if (count($problems) > 0) {
    error_log('OIDC configuration problems: ' . implode('; ', $problems));
    oidc_callback_fail('Sign-in through ' . $config['provider_name'] . ' is not set up correctly. Please contact convention staff.');
}

if (isset($_GET['error'])) {
    $error = (string)$_GET['error'];
    $stateMatches = $pending && isset($_GET['state']) && is_string($_GET['state']) && hash_equals($pending['state'], $_GET['state']);
    if ($stateMatches && $pending['silent'] && in_array($error, ['login_required', 'interaction_required', 'consent_required', 'account_selection_required'], true)) {
        // Silent sign-in found no provider session. Just show the normal login page.
        unset($headerErrorMessage);
        require('login.php');
        exit();
    }
    error_log("OIDC callback error '$error'" . ($stateMatches ? '' : ' (state did not match)'));
    if ($error === 'access_denied') {
        oidc_callback_fail('Your ' . $config['provider_name'] . ' account has no membership for ' . CON_NAME
            . '. You need a confirmed membership to use PlanZ.');
    }
    oidc_callback_fail('Sign-in through ' . $config['provider_name'] . ' did not succeed. Please try again.');
}

try {
    $result = oidc_complete_authorization($config, $pending, $_GET);
    $userinfo = $result['userinfo'];
    $members = oidc_selectable_members(oidc_event_members($userinfo, $config), $config);
} catch (OidcException $e) {
    error_log($e->getMessage());
    oidc_callback_fail($e->userMessage);
}

if (count($members) === 0) {
    error_log("OIDC: user sub {$result['id_token']['sub']} has no usable member for event {$config['event_id']}");
    // Members of this event that were dropped because they don't have a badge ID yet.
    $inEvent = array_filter((array)($userinfo[$config['members_claim']] ?? []),
        fn($m) => is_array($m) && isset($m['eid']) && (string)$m['eid'] === (string)$config['event_id']);
    if ($config['auto_provision'] && count($inEvent) > 0) {
        oidc_callback_fail('Your membership for ' . CON_NAME . ' is not ready for PlanZ yet. Please try again later, or contact '
            . (defined('PROGRAM_EMAIL') && PROGRAM_EMAIL ? PROGRAM_EMAIL : 'the programme team') . '.');
    }
    if ($config['auto_provision']) {
        oidc_callback_fail('Your ' . $config['provider_name'] . ' account has no membership for ' . CON_NAME
            . '. You need a confirmed membership to use PlanZ.');
    }
    oidc_callback_fail('You have not been set up as a participant in PlanZ yet. Please contact '
        . (defined('PROGRAM_EMAIL') && PROGRAM_EMAIL ? PROGRAM_EMAIL : 'the programme team') . '.');
}

// Start from a clean session: a previous user's sign-in must not leak into this one.
foreach (array_keys($_SESSION) as $key) {
    if ($key !== 'oidc_silent_tried') {
        unset($_SESSION[$key]);
    }
}
$_SESSION['oidc_sub'] = $result['id_token']['sub'];
$_SESSION['oidc_members'] = $members;
$_SESSION['oidc_entitlements'] = oidc_entitlements($userinfo, $config);
$_SESSION['oidc_silent_tried'] = true;
if (!empty($_COOKIE['planz_logged_out'])) {
    // Signed in again on purpose, so silent sign-in may be used again.
    setcookie('planz_logged_out', '', ['expires' => 1, 'path' => BASE_PATH, 'secure' => !empty($_SERVER['HTTPS']), 'httponly' => true, 'samesite' => 'Lax']);
}

if (count($members) === 1) {
    try {
        oidc_activate_member($members[0], $config);
    } catch (OidcException $e) {
        error_log($e->getMessage());
        oidc_callback_fail($e->userMessage);
    }
    header('Location: ./' . oidc_landing_page());
    exit();
}

header('Location: ./SelectParticipant.php');
exit();
