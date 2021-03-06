<?php


function start_session_if_necessary() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

function getBadgeId() {
    return isLoggedIn() ? $_SESSION['badgeid'] : null;
}

function isLoggedIn() {
    return isset($_SESSION['badgeid']);
}

function getLoggedInUserBadgeId() {
    return isLoggedIn() ? $_SESSION['badgeid'] : null;
}

function isProgrammingStaff() {
    return isLoggedIn() && may_I("Staff");
}

function isVolunteerSetUpAllowed() {
    return isLoggedIn() && may_I("Volunteering Set-up");
}


?>