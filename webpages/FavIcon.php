<?php
//	Created by James Shields
//  This file providees a consistent URL for requesting the PlanZ configurable "favicon" image

require_once('./config/db_name.php');

if (defined('CON_HEADER_IMG') && CON_THEME_FAVICON !== "") {
    header('Location: ' . CON_THEME_FAVICON);
} else {
    header('Location: images/favicon.ico');
}

?>