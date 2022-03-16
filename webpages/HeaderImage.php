<?php
//	Created by BC Holmes
//  This file providees a consistent URL for requesting the PlanZ configurable header image

if (!include ('./config/db_name.php')) {
    include ('./config/db_name.php');
}

if (defined('CON_HEADER_IMG') && CON_HEADER_IMG !== "") {
    header('Location: ' . CON_HEADER_IMG);
} else {
    header('Location: images/Z_illuminated.jpg');
}

?>
