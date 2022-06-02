<?php

class Authentication {
    function isLoggedIn() {
        return isset($_SESSION['badgeid']);
    }

    function isAdminModulesAllowed() {
        return isLoggedIn() && may_I("AdminModules");
    }
}

?>