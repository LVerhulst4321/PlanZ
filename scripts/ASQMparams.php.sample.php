<?php
// This is an example file.  Please copy to ASQMparams.php and edit as needed.
// Used by the script: AutoSendQueuedMail.php

define("DBHOSTNAME","planz.mywebhost.com");
define("DBUSERID", getenv("MYSQL_USER"));
define("DBPASSWORD", getenv("MYSQL_PASSWORD"));
define("DBDB","sampledb_prod");
define("EmailSpoolerLogFile","/var/data/planz/reportlogs/AutoSendQueuedMail.log");
?>
