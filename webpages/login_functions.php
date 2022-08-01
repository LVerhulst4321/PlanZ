<?php

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