<?php

require_once(__DIR__ . '/../data_functions.php');

class Authentication {
    function isLoggedIn() {
        return isset($_SESSION['badgeid']);
    }

    function getBadgeId() {
        return $this->isLoggedIn() ? $_SESSION['badgeid'] : null;
    }

    function isAdminModulesAllowed() {
        return $this->isLoggedIn() && may_I("AdminModules");
    }

    function isVolunteerSetUpAllowed() {
        return $this->isLoggedIn() && may_I("Volunteering Set-up");
    }

    function isSubmitBrainstormAllowed() {
        return $this->isLoggedIn() && may_I('BrainstormSubmit');
    }

    function isProgrammingStaff() {
        return $this->isLoggedIn() && may_I("Staff");
    }
}

?>