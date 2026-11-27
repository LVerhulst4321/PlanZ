<?php
// Copyright (c) 2026 PlanZ contributors. All rights reserved. See copyright document for more details.
//
// Starts an OpenID Connect sign-in: stores state, nonce and the PKCE verifier in the session, and
// redirects the browser to the provider. With ?silent=1 it asks the provider not to show a login
// page (prompt=none), so a user already signed in there comes straight back.
//
// This is also the "initiate login URL" for third-party initiated login: the provider (for example
// a button in ConReg's member portal) sends the browser here, by GET or POST, with "iss" and
// optionally "login_hint" and "target_link_uri", and the sign-in starts straight away. Someone
// already signed in to PlanZ is signed in again, so PlanZ's user matches the provider's.
global $headerErrorMessage, $title;
require_once('CommonCode.php');
require_once('oidc_functions.php');

if (!is_oidc_login_enabled()) {
    header('Location: ./login.php');
    exit();
}

$config = oidc_config();
$problems = oidc_config_problems($config);
if (count($problems) > 0) {
    error_log('OIDC configuration problems: ' . implode('; ', $problems));
    $headerErrorMessage = 'Sign-in through ' . $config['provider_name'] . ' is not set up correctly. Please contact convention staff.';
    $_SESSION['oidc_silent_tried'] = true;
    require('login.php');
    exit();
}

try {
    $initiate = oidc_initiate_request($config, $_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST : $_GET);
} catch (OidcException $e) {
    // Not from our provider: don't start a sign-in, just show the login page.
    error_log($e->getMessage());
    $_SESSION['oidc_silent_tried'] = true;
    require('login.php');
    exit();
}

$silent = isset($_GET['silent']) && $_GET['silent'] === '1';
if ($silent) {
    // Only ever try silent sign-in once per browser session, or a user who isn't signed in to the
    // provider would loop between the two sites.
    $_SESSION['oidc_silent_tried'] = true;
}

try {
    $discovery = oidc_fetch_discovery($config);
} catch (OidcException $e) {
    error_log($e->getMessage());
    $headerErrorMessage = $e->userMessage;
    require('login.php');
    exit();
}

$pending = oidc_new_pending($silent, null, $initiate);
$_SESSION['oidc_pending'] = $pending;
header('Location: ' . oidc_authorize_url($config, $discovery, $pending));
exit();
