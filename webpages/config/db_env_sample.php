<?php
// Copyright (c) 2008-2021 Peter Olszowka. All rights reserved.
// See copyright document for more details.

// This file contains items that are defined by the environment variables and are more system oriented.
// This file should be placed in a secure location.
// This file should be named db.php.
// This file is included by the db_name.php file.

define("DBHOSTNAME", "");
define("DBUSERID", getenv("MYSQL_USER"));
define("DBPASSWORD", getenv("MYSQL_PASSWORD"));
define("DBDB", "");
define("DBVER", "5.7");   //Version of mysql
//define("DBVER", "8.0.28");  //Version of mysql

define("SMTP_ADDRESS", getenv("SMTP_SERVER")); // See documentation for your mail relay service.
define("SMTP_PORT", getenv("SMTP_PORT")); // Likely options are "587", "2525", "25", or "465".  See documentation for your mail relay service.
define("SMTP_PROTOCOL", "TLS"); // Options are "", "SSL", or "TLS".  Blank/Default is no encryption. See documentation for your mail relay service.
define("SMTP_USER", getenv("SMTP_USER_NAME")); // Use "" to skip authentication. See documentation for your mail relay service.
define("SMTP_PASSWORD", getenv("SMTP_PASSWORD")); // Use "" to skip authentication. See documentation for your mail relay service.

        // Self service reset of password via email link requires use of reCAPTCHA to prevent bad actors from using page to send email
define("RECAPTCHA_SITE_KEY", ""); // Register the domain you use for PlanZ with Google reCAPTCHA to acquire site key ...
define("RECAPTCHA_SERVER_KEY", ""); // ... and server key

define("ROOT_URL", 'https://' . getenv("HOSTNAME") . '/'); // URL to reach this server. Required to generate an email password reset link.

define("ENCRYPT_KEY", ""); // used for encrypting hidden inputs; I suggest finding a random password generator and putting in a 64 character alphanumeric only password

define("CUSTOM_LOGIN_PHP", ""); // Custom login function connected to reg system


?>
