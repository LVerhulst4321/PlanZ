<?php


function start_session_if_necessary() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

?>