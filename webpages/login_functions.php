<?php

use PlanZ\PlanZModule;

/*
 * get_login_mode:
 *  Which sign-in methods are offered, from LOGIN_MODE in db_name.php:
 *      "password" - PlanZ user ID or email and password (the default)
 *      "oidc"     - sign in through an OpenID Connect provider, such as ConReg, only
 *      "both"     - either
 */
function get_login_mode() {
    $mode = defined('LOGIN_MODE') ? strtolower((string)LOGIN_MODE) : 'password';
    return in_array($mode, array('password', 'oidc', 'both'), true) ? $mode : 'password';
}

function is_password_login_enabled() {
    return get_login_mode() !== 'oidc';
}

function is_oidc_login_enabled() {
    return get_login_mode() !== 'password';
}

/*
 * get_oidc_provider_name:
 *  The name users know the sign-in provider by, used in messages.
 */
function get_oidc_provider_name() {
    return defined('OIDC_PROVIDER_NAME') && OIDC_PROVIDER_NAME !== '' ? OIDC_PROVIDER_NAME : 'the registration system';
}

/*
 * get_oidc_login_button_text:
 *  Text of the OIDC sign-in button: OIDC_LOGIN_BUTTON_TEXT, or "Sign in with <provider name>".
 */
function get_oidc_login_button_text() {
    if (defined('OIDC_LOGIN_BUTTON_TEXT') && trim(OIDC_LOGIN_BUTTON_TEXT) !== '') {
        return trim(OIDC_LOGIN_BUTTON_TEXT);
    }
    return 'Sign in with ' . get_oidc_provider_name();
}

/*
 * is_oidc_session:
 *  True if the current session was signed in through OIDC.
 */
function is_oidc_session() {
    return isset($_SESSION['auth_method']) && $_SESSION['auth_method'] === 'oidc';
}

/*
 * can_reset_own_password:
 *  Password self-service (reset by email, change on the profile page) is only offered when password
 *  sign-in is enabled and the current session (if any) didn't come from OIDC.
 */
function can_reset_own_password() {
    return defined('RESET_PASSWORD_SELF') && RESET_PASSWORD_SELF === true
        && is_password_login_enabled() && !is_oidc_session();
}

/*
 * can_switch_participant:
 *  True if an OIDC session can act as more than one member.
 */
function can_switch_participant() {
    return is_oidc_session() && isset($_SESSION['oidc_members']) && count($_SESSION['oidc_members']) > 1;
}

/*
 * is_email_login_supported:
 *  If the installation has specified the options for login via email address in the installation's,
 *  db_name.php file then return true; else return false (which means that only login by badgeid
 *  is supported).
 */
function is_email_login_supported() {
    return defined('EMAIL_LOGIN_SUPPORTED') && EMAIL_LOGIN_SUPPORTED === true;
}

/*
 * get_user_id_prompt:
 *  Returns The name of the field that contains the user's login id (often "User ID" or "Badge ID").
 *  If the PlanZ installation supports login with email address, we'll tweak the field name,
 *  but generally we should favour the text that the support team put in the db_name.php file.
 */
function get_user_id_prompt() {
    if (defined('LOGIN_PAGE_USER_ID_PROMPT') && LOGIN_PAGE_USER_ID_PROMPT !== '') {
        return LOGIN_PAGE_USER_ID_PROMPT;
    } else if (!is_email_login_supported()) {
        return USER_ID_PROMPT;
    } else {
        return USER_ID_PROMPT." or Email address";
    }
}

function is_logged_in() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return (isset($_SESSION['badgeid']));
}

/*
 * Update the login time on the user record in CongoDump.
 */
function set_login_time($badgeid) {
    $query = <<<EOD
    UPDATE CongoDump
    SET last_login=NOW()
    WHERE badgeid='$badgeid';
EOD;
    if (!$result = mysqli_query_with_error_handling($query, true)) {
        return false;
    }
    return true;
}

// Function set_modules()
// This function gets a list of all of the enabled modules
//
function set_modules($db) {
    try {
        $modules = PlanZModule::findAll($db);

        $_SESSION['modules'] = array();
        foreach ($modules as $m) {
            $_SESSION['modules'][$m->packageName] = $m->isEnabled;
        }
        return true;
    } catch (Exception $ex) {
        return false;
    }
}

// Function set_permission_set($badgeid)
// Performs complicated join to get the set of permission atoms available to the user
// Stores them in global variable $permission_set
//
function set_permission_set($badgeid, $db) {
    $permissions = array();
    $query = <<<EOD
SELECT DISTINCT
        permatomtag
    FROM
                  PermissionAtoms PA
             JOIN Permissions P USING (permatomid)
        LEFT JOIN Phases PH ON P.phaseid = PH.phaseid AND PH.current = TRUE
        LEFT JOIN UserHasPermissionRole UHPR ON P.permroleid = UHPR.permroleid AND UHPR.badgeid='$badgeid'
    WHERE
            (PH.phaseid IS NOT NULL OR P.phaseid IS NULL)
        AND (UHPR.badgeid IS NOT NULL OR P.badgeid='$badgeid')
        AND (PA.module_id is null
			OR PA.module_id in (select id from module where is_enabled = 1));
EOD;
    $resultSet = mysqli_query($db, $query);
    if ($resultSet) {
        while ($row = mysqli_fetch_object($resultSet)) {
            $permissions[] = $row->permatomtag;
        }
        mysqli_free_result($resultSet);

        $_SESSION['permission_set'] = $permissions;
    }

    return true;
}

?>
