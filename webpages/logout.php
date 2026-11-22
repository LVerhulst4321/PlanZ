<?php
//	Copyright (c) 2011-2020 Peter Olszowka. All rights reserved. See copyright document for more details.
require_once('PartCommonCode.php');
$wasOidc = is_oidc_session();
session_destroy();                 // Destroy session data
$_SESSION = array();               // Unset session data
unset($_COOKIE[session_name()]);   // Clear cookie
if (is_oidc_login_enabled()) {
    // Stops silent OIDC sign-in from signing the user straight back in for the rest of this
    // browser session. Logging out of PlanZ does not log out of the provider.
    setcookie('planz_logged_out', '1', ['path' => BASE_PATH, 'secure' => !empty($_SERVER['HTTPS']), 'httponly' => true, 'samesite' => 'Lax']);
}
participant_header("Logout", false, 'Logout', true);
if ($wasOidc && defined('OIDC_LOGOUT_URL') && OIDC_LOGOUT_URL !== '') {
    $providerName = get_oidc_provider_name();
?>
    <div class="container">
        <p>You are still signed in to <?php echo htmlspecialchars($providerName, ENT_QUOTES, 'UTF-8'); ?>.
            <a href="<?php echo htmlspecialchars(OIDC_LOGOUT_URL, ENT_QUOTES, 'UTF-8'); ?>">Log out of
            <?php echo htmlspecialchars($providerName, ENT_QUOTES, 'UTF-8'); ?> too</a>.</p>
    </div>
<?php
}
participant_footer();
?>
