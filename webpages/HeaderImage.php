<?php
//	Created by BC Holmes
//  This file providees a consistent URL for requesting the PlanZ configurable header image

if (file_exists(__DIR__ . '/config/db_name.php')) {
    include __DIR__ . '/config/db_name.php';
}

if (defined('CON_HEADER_IMG') && CON_HEADER_IMG !== "") {
    header('Location: ' . CON_HEADER_IMG);
} else {
    header('Location: images/Plan-Z-Logo-250.png');
}

?>
