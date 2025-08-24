<?php
// This is an example file.  Please copy to config folder and name it db_name.php and edit as needed.
// Copyright (c) 2008-2021 Peter Olszowka. All rights reserved.
// See copyright document for more details.

// This should reference the environment variables file.
if( file_exists( '/etc/planz/db_env_sample.php' ) ) {
    require_once( '/etc/planz/db_env_sample.php' );
} else {
    echo '<strong>FATAL ERROR: Config file of environment variables not found.</strong><br />';
}


define("CON_NAME", "PlanZ Demo");
define("CON_URL", "http://samplecon.org");
define("BASE_PATH", "/"); // Base path links will be relative to. Only change if hosting in a subdirectory.
define("BRAINSTORM_EMAIL", "brain@somewhere.net");
define("PROGRAM_EMAIL", "program@somewhere.net");
define("CON_NUM_DAYS", 3); // code works for 1 - 8
define("PHP_DEFAULT_TIMEZONE", "AMERICA/NEW_YORK"); // must be valid argument to php date_default_timezone_set()  Should correspond with DB configuration
define("DB_DEFAULT_TIMEZONE", "US/Eastern"); // must be valid argument to set time_zone,  Should correspond with PHP configuration
define("CON_START_DATIM", "2009-08-06 00:00:00"); // Broadly used.  Must be in mysql format: "YYYY-MM-DD HH:MM:SS" (HH:00-23) HH:MM:SS probably should be 00:00:00
define("DAY_CUTOFF_HOUR", 8); // times before this hour (of 0-23) are considered previous day
        // used for Participant Availability only
define("FIRST_DAY_START_TIME", "15:00"); // next 5 are for grid scheduler
define("OTHER_DAY_STOP_TIME", "25:00");
define("OTHER_DAY_START_TIME", "8:30");
define("LAST_DAY_STOP_TIME", "16:00");
define("STANDARD_BLOCK_LENGTH", "1:30"); // "1:00" and "1:30" are only values supported
        // Block includes length of panel plus time to get to next panel, e.g. 55 min plus 5 min.
define("DISPLAY_24_HOUR_TIME", FALSE); // TRUE: times in 24 hour clock. FALSE: times in 12 hour clock.
define("DURATION_IN_MINUTES", FALSE); // TRUE: in mmm; FALSE: in hh:mm
        // affects session edit/create page only, not reports
define("DEFAULT_DURATION", "1:15"); // must correspond to DURATION_IN_MINUTES
define("SMTP_QUEUEONLY", FALSE); // TRUE = add to DB queue, schedule /scripts/processEmailQueue.php as a cron job do the send; FALSE send immediately, add to queue only on transport failure
define("SMTP_MAX_MESSAGES", "100"); // Maximum number of messages to send per cron run. Set to 0 for no limit.
define("PREF_TTL_SESNS_LMT", 10); // Input data verification limit for preferred total number of sessions
define("PREF_DLY_SESNS_LMT", 5); // Input data verification limit for preferred daily limit of sessions
define("AVAILABILITY_ROWS", 8); // Number of rows of availability records to render
define("MAX_BIO_LEN", 1000); // Maximum length (in characters) permitted for participant biographies
define("MY_AVAIL_KIDS", FALSE); // Enables questions regarding no. of kids in Fasttrack on "My Availability"
define("ENABLE_SHARE_EMAIL_QUESTION", TRUE); // Enables question regarding sharing participant email address
define("ENABLE_USE_PHOTO_QUESTION", TRUE); // Enables question regarding using participant photo for promotional purposes
define("ENABLE_ALLOW_STREAMING_QUESTION", TRUE); // Enables question asking if participant is willing to be streamed on sessions.
define("ENABLE_ALLOW_RECORDING_QUESTION", TRUE); // Enables question asking if participant is willing to be recorded for playback sessions.
define("ENABLE_BESTWAY_QUESTION", FALSE); // Enables question regarding best way to contact participant
define("ALLOW_ASSIGN_UNRANKED_PARTICIPANTS", FALSE); // If true, show participants on Assign Participants page if they have not ranked sessions.
define("TITLE_MIN_LENGTH", 10); // Title must be at least this long.
define("TITLE_MAX_LENGTH", 50); // Title must not be longer than this. Note that the current limit of the DB field is 100 characters.
define("BILINGUAL", TRUE); // Triggers extra fields in Session and "My General Interests"
define("SECOND_LANG", "FRENCH");
define("SECOND_TITLE_CAPTION", "Titre en fran&ccedil;ais");
define("SECOND_DESCRIPTION_CAPTION", "Description en fran&ccedil;ais");
define("SECOND_BIOGRAPHY_CAPTION", "Biographie en fran&ccedil;ais");
define("SECOND_SESSION_SECTION", FALSE); // Show and allow editing of the second session description (assuming bilingual is off)
define("SHOW_BRAINSTORM_LOGIN_HINT", FALSE);
define("USER_ID_PROMPT", "User ID"); // What to label User ID / Badge ID / Email
define("LOGIN_PAGE_USER_ID_PROMPT", ""); // What to label User ID / Badge ID specifically on the login page (blank defaults to the value of USER_ID_PROMPT)
define("EMAIL_LOGIN_SUPPORTED", TRUE); // Can users use their email address as a userid?
define("RESET_PASSWORD", TRUE); // Staff can reset a user's password.
define("RESET_PASSWORD_SELF", TRUE); // User can reset own password.  Requires email and Turnstile integration.
define("GRID_START_DATIM","2009-08-06 12:00:00"); // Used by special grid report
define("GRID_END_DATIM","2009-08-08 20:00:00"); // Used by special grid report
define("PASSWORD_RESET_LINK_TIMEOUT", "PT01H"); // How long until password reset link expires See https://www.php.net/manual/en/dateinterval.construct.php for format.
define("PASSWORD_RESET_LINK_TIMEOUT_DISPLAY", "1 hour"); // Text description of PASSWORD_RESET_LINK_TIMEOUT
        // Self service reset of password via email link requires use of Turnstile to prevent bad actors from using page to send email
define("PASSWORD_RESET_FROM_EMAIL", "admin@somewhere.net"); // From address to be used for password reset emails
define("PASSWORD_RESET_FROM_EMAIL_NAME", "PlanZ Admin"); // The name accompanying the from address to be used for password reset emails
define("DEFAULT_USER_PASSWORD", "changeme"); // Note, PlanZ will never directly set a user's password to this default nor will it
        // create users with a default password, but some external integrations to create users do so.  In that case, PlanZ can
        // identify users with this default password and prompt them to change it as well as report to staff. If your installation
        // does not use a default password, leave this empty ''.
define("NEW_ACCOUNT_LINK_TIMEOUT", "PT48H"); // How long until new account link expires See https://www.php.net/manual/en/dateinterval.construct.php for format.
define("NEW_ACCOUNT_LINK_TIMEOUT_DISPLAY", "2 days"); // Text description of NEW_ACCOUNT_LINK_TIMEOUT
define("TRACK_TAG_USAGE", "TAG_OVER_TRACK"); // Describe how Track and Tag fields are used -- one of 4 following values:
        // "TAG_ONLY" : Track field is not used and will be hidden where possible.
        //      NOTE: TAG_ONLY requires that trackid 1 exist in Tracks, be the hidden track for TAG_ONLY and have selfselect be set to 1 (1, "Tag Based", 10, 1)
        // "TAG_OVER_TRACK" : Both fields are used, but primary sorting and filtering is by Tag.
        // "TRACK_OVER_TAG" : Both fields are used, but primary sorting and filtering is by Track.
        // "TRACK_ONLY" : Tag field is not used and will be hidden where possible.
define("REQUIRE_CONSENT", TRUE); // Require Data Collection Consent from all users
define("USE_REG_SYSTEM", FALSE);
        // True -> PlanZ users loaded from reg system into CongoDump; staff users cannot edit them
        // False -> PlanZ users can be created and edited by staff users in PlanZ
define("REGISTRATION_URL", "");
define("USE_REGTYPE_DESCRIPTION", FALSE);
        // False -> Display regtype field as registration type - name of registration type in regtype.
        // True -> Display RegTypes.message - registration type code in regtype, description in message field.

define("USE_DAY_JOB", TRUE); // Let participants specify their daytime occupation on Personal Details page.
define("LABEL_DAY_JOB", "Day Job:"); // Label for daytime occupation.
define("USE_AGE_RANGE", TRUE); // Let participants specify their age group.
define('LABEL_AGE_RANGE', "Age Range:"); // Label for age group.
define("USE_ETHNICITY", TRUE); // Let participants specify their ethnicity.
define('LABEL_ETHNICITY', "Race/Ethnicity:"); // Label for ethnicity.
define("USE_ACCESSIBILITY", TRUE); // Let participants specify their accessibility issues.
define('LABEL_ACCESSIBILITY', "Do you have any accessibility issues that we should be aware of?"); // Label for accessibility issues.
define("USE_GENDER", TRUE); // Let participants specify their gender.
define('LABEL_GENDER', "Gender:"); // Label for gender on Personal Details page.
define("USE_SEXUAL_ORIENTATION", TRUE); // Let participants specify their sexual orientation.
define('LABEL_SEXUAL_ORIENTATION', "Sexual Orientation:"); // Label for sexual orientation.
define("USE_PRONOUNS", TRUE); // Let participants specify their pronouns on Personal Details page.
define("LABEL_PRONOUNS_ARE", "My pronouns are:"); // Label for pronouns drop-down.
define("LABEL_PRONOUNS_OTHER", "If you selected \"other\" for your pronouns, provide your pronouns here:"); // Label for "other" pronouns.

define('MY_SCHEDULE_SHOW_COMMENTS', TRUE); // Should participant comments be shown on My Schedule?

define("REG_PART_PREFIX", ""); // only needed for USE_REG_SYSTEM = FALSE; prefix portion of userid/badgeid before counter; can be empty string for no prefix
define("REG_PART_DIGITS", 4); // only needed for USE_REG_SYSTEM = FALSE; number of digits to pad counter; if number has fewer than specified digits, will left pad with zeros
define("HTML_BIO", TRUE); // Allow editing BIO as HTML and saving it both as plain text and HTML
define("HTML_SESSION", TRUE); // Allow editing Session Description as HTML and saving it both as plain text and HTML
define("MEETING_LINK", TRUE); // Add support for Meeting link in sessions
define("STREAMING_LINK", TRUE); // Add support for streaming link in sessions
define("STREAMING_LABEL", "Streaming link"); // Specify label for streaming link - allows conventions to specify specific streaming service if required
define("SIGNUP_LINK", TRUE); // Add support for signup link in sessions

// Items for Photo Upload/Approval
define("PARTICIPANT_PHOTOS", TRUE); // enable the participant photo feature
define("PHOTO_UPLOAD_DIRECTORY", "../upload_photos");  // outside of web server path, only served by PHP
define("PHOTO_PUBLIC_DIRECTORY", "/participant_photos"); // inside of web server path, can be served outside of PHP
define("PHOTO_FILE_TYPES", "jpg,png"); // comma separated list of allowed file types/suffixes (will be verified by PHP)  // not actually used in code
define("PHOTO_DIMENSIONS", "200,200,800,800,1048576"); // comma separated list: min width, min height, max width, max height, file size
define("PHOTO_DEFAULT_IMAGE", "default.png"); // placeholder image for participants without photo

define("JSON_EXTRACT_DIRECTORY", "/var/data/guide/");  // Path to directory where Konopas/ConClár files to be written.
define("JSON_EXTRACT_ASSIGN_VARS", FALSE); // If TRUE include variable names in JSON output files (required for KonOpas).
define("OBS_EXTRACT_DIRECTORY", "obs"); // Path to directory for OBS files, relative to web root.
define('OBS_EXTRACT_TAGS', ''); // Comma separated list of Tags to generate OBS extracts for.
        // Prefix Tag with ~ in list to extract all items without tag.

define("PHOTO_EXTRACT_LINK_TYPE", ""); // Link type to use when exporting photos to KonOpas/ConClár.
        // Supported values: "img", "photo" or "img_256_url" (the last one comes from Grenadine, so not really recommended).
        // Leave blank to disable image exporting.

define("USING_SURVEYS", FALSE); // enable the survey feature

define("CON_THEME", "");
        // if con-specific theming should be applied, you can reference a theme css here.
        // for example: define("CON_THEME", "themes/reallybigcon/main.css");
define("CON_THEME_FAVICON", "");
        // if you want a con-specific favicon , you can reference an image file here.
        // for example: define("CON_THEME_FAVICON", "themes/reallybigcon/myfavicon.png");
define("CON_HEADER_IMG", "");
        // to improve the con branding, you can define a con-specific header image that will take the place of the
        // PlanZ illustrated "Z" image, like so: define("CON_HEADER_IMG", "themes/reallybigcon/header.jpg");
define("CON_HEADER_IMG_ALT", "");
        // to improve the con branding, you can specify the alt-text of the header image. For example:
        // define("CON_HEADER_IMG_ALT", "Really Big Con Logo);

define("CON_SUPPORT_EMAIL", ""); // From address to be used for password reset emails

define("REPORT_INCLUDE_DIRECTORY", "/var/data/planz/");  // outside of web server path, only served by PHP

define("PUBLIC_NEW_USER", FALSE); // allow new user creation from login screen

define("CONFIRM_SESSION_ASSIGNMENT", TRUE); // Ask participants to confirm their assignments

define("WEBHOOK_TIME_TOLERANCE", "PT05M"); // How long to allow signed webhook requests to be considered valid.
?>
