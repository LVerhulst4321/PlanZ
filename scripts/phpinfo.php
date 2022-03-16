<?php

if( file_exists( './config/db_name.php' ) ) {
    echo '<strong>Config file found.</strong><br />';
} else {
    echo '<strong>FATAL ERROR: Config file not found.</strong><br />';
}
echo '<br />';
if( file_exists( '/etc/db.php' ) ) {
    echo '<strong>PlanZ Config file found.</strong><br />';
} else {
    echo '<strong>FATAL ERROR: PlanZ Config file not found.</strong><br />';
}
echo '<br />';
if( file_exists( '/etc/planz/db.php' ) ) {
    echo '<strong>PlanZ Config file 2 found.</strong><br />';
} else {
    echo '<strong>FATAL ERROR: PlanZ Config file 2 not found.</strong><br />';
}
echo '<br />';
//echo 'MYSQL_USER = ' . getenv("MYSQL_USER") . '<br />';
//echo 'MYSQL_PASSWORD = ' . getenv("MYSQL_PASSWORD") . '<br />';
echo 'HOSTNAME = ' . getenv("HOSTNAME") . '<br />';
echo 'SMTP_SERVER = ' . getenv("SMTP_SERVER") . '<br />';
echo 'SMTP_PORT = ' . getenv("SMTP_PORT") . '<br />';
echo 'SMTP_USER_NAME = ' . getenv("SMTP_USER_NAME") . '<br />';
//echo 'SMTP_PASSWORD = ' . getenv("SMTP_PASSWORD") . '<br />';



phpinfo(); 
?>