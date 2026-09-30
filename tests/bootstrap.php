<?php
// PHPUnit bootstrap for PlanZ.
//
// This deliberately does NOT require CommonCode.php or PartCommonCode.php: those
// files call session_start() and connect to the database as a side effect of being
// included, which is wrong for fast, isolated unit tests. Instead we pull in just
// the pure function-definition files (no top-level side effects) plus the app's
// config constants, and let Functional tests open their own DB/HTTP connections
// explicitly via tests/TestDb.php and tests/HttpClient.php.

define('PLANZ_ROOT', dirname(__DIR__) . '/webpages');

// Dedicated fixture participant used by Functional tests. Never reuse a real or
// manually-tested account (e.g. someone@somewhere.com) here: tests delete and
// recreate this badgeid's rows on every run.
define('PLANZ_TEST_BADGEID', 'PLNZTEST1');
define('PLANZ_TEST_PASSWORD', 'PlanZTestPass1!');
define('PLANZ_TEST_PASSWORD_HASH', '$2y$10$rbqE07ER2kV.n3jqHdlqge41GqQRvGf9Z7wph/GsNY5gmfWP10NMK');
define('PLANZ_TEST_EMAIL', 'planz-test-user@example.invalid');

require_once PLANZ_ROOT . '/Constants.php';

// Config constants (CON_NUM_DAYS, PREF_TTL_SESNS_LMT, AVAILABILITY_ROWS, ...) come
// from webpages/config/db_name.php. Locally this already exists (DDEV setup); in CI
// the workflow generates one pointed at the CI database before tests run.
if (file_exists(PLANZ_ROOT . '/config/db_name.php')) {
    require_once PLANZ_ROOT . '/config/db_name.php';
} else {
    fwrite(STDERR, "webpages/config/db_name.php not found — copy webpages/config/db_name_sample.php" .
        " and configure it before running tests.\n");
    exit(1);
}

require_once PLANZ_ROOT . '/data_functions.php';
require_once PLANZ_ROOT . '/validation_functions.php';
require_once PLANZ_ROOT . '/my_sched_constr_func.php';

require_once __DIR__ . '/TestDb.php';
require_once __DIR__ . '/HttpClient.php';
require_once __DIR__ . '/SessionForge.php';
