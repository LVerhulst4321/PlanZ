<?php
/**
 * Script for applying PlanZ database patches.
 *
 * Usage:
 *   php scripts/db_update.php
 * (run from base directory of PlanZ).
 * DB settings must be configured before running.
 * php version 7+.
 *
 * @category Script
 * @package  PlanZ
 * @author   James Shields <james@lostcarpark.com>
 * @license  Zambia License, version 1.1
 * @link     https://github.com/LVerhulst4321/PlanZ
 */

/**
 * This function will take a given $file and execute it directly in php.
 * Adapted from: https://stackoverflow.com/questions/7038739/execute-a-sql-file-using-php/41404203#41404203
 * It tries three methods so it should almost allways work.
 * method 1: Directly via cli using mysql CLI interface. (Best choice)
 * method 2: use mysqli_multi_query
 * method 3: use PDO exec
 * It tries them in that order and checks to make sure they WILL work based on
 * various equirements of those options.
 *
 * @param string $file - The SQL script file to be run.
 *
 * @return bool
 */
function executeSQL(string $file): bool
{
    global $linki;

    //1st method; directly via mysql
    $mysql_paths = array();
    //use mysql location from `which` command.
    $mysql = trim(`which mysql`);
    if (is_executable($mysql)) {
        array_unshift($mysql_paths, $mysql);
    }
    //Default paths
    $mysql_paths[] = '/Applications/MAMP/Library/bin/mysql'; //Mac Mamp
    $mysql_paths[] = 'c:\xampp\mysql\bin\mysql.exe'; //XAMPP
    $mysql_paths[] = '/usr/bin/mysql'; //Linux
    $mysql_paths[] = '/usr/local/mysql/bin/mysql'; //Mac
    $mysql_paths[] = '/usr/local/bin/mysql'; //Linux
    $mysql_paths[] = '/usr/mysql/bin/mysql'; //Linux
    $database = escapeshellarg(DBDB);
    $db_hostname = escapeshellarg(DBHOSTNAME);
    $db_username = escapeshellarg(DBUSERID);
    $db_password = escapeshellarg(DBPASSWORD);
    $file_to_execute = escapeshellarg($file);
    foreach ($mysql_paths as $mysql) {
        if (is_executable($mysql)) {
            $execute_command
                = "\"$mysql\" --host=$db_hostname --user=$db_username "
                . "--password=$db_password $database < $file_to_execute";
            $status = false;
            system($execute_command, $status);
            return $status == 0;
        }
    }

    if (function_exists('mysqli_multi_query')) {
        //2nd method; using mysqli
        mysqli_multi_query($linki, file_get_contents($file));
        //Make sure this keeps php waiting for queries to be done
        do {
        } while (mysqli_more_results($linki) && mysqli_next_result($linki));
        return true;
    }

    // 3rd Method Use PDO as command. See http://stackoverflow.com/a/6461110/627473
    // Needs php 5.3, mysqlnd driver
    $mysqlnd = function_exists('mysqli_fetch_all');

    if ($mysqlnd && version_compare(PHP_VERSION, '5.3.0') >= 0) {
        $database = DBDB;
        $db_hostname = DBHOSTNAME;
        $db_username = DBUSERID;
        $db_password = DBPASSWORD;
        $dsn = "mysql:dbname=$database;host=$db_hostname";
        $db = new PDO($dsn, $db_username, $db_password);
        $db->setAttribute(PDO::ATTR_EMULATE_PREPARES, 0);
        $sql = file_get_contents($file);
        $db->exec($sql);

        return true;

    }

    return false;
}

/**
 * Return true if specified table exists in database.
 *
 * @param string $tableName - The name of the table to check presence of.
 *
 * @return bool
 */
function checkTableExists(string $tableName): bool
{
    global $linki;
    $dbName = DBDB;
    $result = mysqli_query(
        $linki, <<<EOC
            SELECT count((1)) AS `ct`
            FROM INFORMATION_SCHEMA.TABLES
            WHERE table_schema ='$dbName' AND table_name='$tableName';
        EOC
    );
    $row = mysqli_fetch_object($result);
    return ($row->ct == 1);
}

/**
 * Get a list of *.sql files in directory.
 *
 * @param string $dir - Directory to check.
 * @param string $ext - File extension to check for.
 *
 * @return array
 */
function getSQLFiles(string $dir, string $ext = 'sql'): array
{
    $files = [];
    $directory = dir($dir);
    while (false !== ($file = $directory->read())) {
        if (preg_match('/\.' . $ext . '$/', $file)) {
            $files[] = $file;
        }
    }
    sort($files);
    return $files;
}

/**
 * Check if PatchLog table exists. If not, assume database not initialized.
 * If not initialized, offer to run a .dump file in the Install directory.
 *
 * @param string $path - The path to the database patches.
 *
 * @return void
 */
function checkDbaseInitialized(string $path): void
{
    if (!checkTableExists('PatchLog')) {
        echo "Table 'PatchLog' not found. Database may not be initialized.\n";
        $response = readline("Would you like to initialize database? (N) ");
        if (empty($response) || !str_starts_with('Y', strtoupper($response))) {
            echo "Unable to continue. Please check database and try again.\n";
            exit(-1);
        }
        $files = getSQLFiles($path . '/../Install', 'dump');
        foreach ($files as $key => $file) {
            echo "[" . ($key + 1) . "] " . $file . "\n";
        }
        $selection = readline('Enter number of initialization script: ');
        if (empty($selection) || !is_numeric($selection)) {
            echo "Input not numeric. Unable to continue.\n";
            exit(-1);
        }
        $fileNum = ((int)$selection) - 1;
        if (!array_key_exists($fileNum, $files)) {
            echo "Number does not correspond to a file. Unable to continue.\n";
            exit(-1);
        }
        echo "Initialising database from " . $files[$fileNum] . "\n";
        if (!executeSQL($path . '/../Install/' . $files[$fileNum])) {
            echo "Database initialization script failed.\n";
            exit(-1);
        }
    }
}

/**
 * Check if patch file is loged in PatchLog table.
 *
 * @param string $fileName - The file to check if already run.
 *
 * @return bool
 */
function checkPatchApplied(string $fileName): bool
{
    global $linki;
    $result = mysqli_query(
        $linki,
        "SELECT count(1) AS `ct` FROM PatchLog WHERE patchname='$fileName';"
    );
    $row = mysqli_fetch_object($result);
    return ($row->ct == 1);
}

/**
 * Loop through all patches in Upgrade_dbase directory, and if not already applied,
 * apply to database.
 *
 * @param string $path - The path to the patch files.
 *
 * @return void
 */
function applyDatabasePatches(string $path): void
{
    $patched = 0;
    $files = getSQLFiles($path . '/../Install/Upgrade_dbase', 'sql');
    foreach ($files as $file) {
        if (!checkPatchApplied($file)) {
            echo "Applying patch $file...\n";
            if (!executeSQL($path . '/../Install/Upgrade_dbase/' . $file)) {
                echo "Error applying patch.\n";
                exit(-1);
            }
            $patched++;
        }
    }
    if ($patched == 0) {
        echo "No new patches found. You're up to date.\n";
        return;
    }
    echo "Applied $patched patch" . ($patched > 0 ? "es" : "") . " successfully.\n";
}

/**
 * Before applying patches, some setup required.
 * - Get path to webpages directory.
 * - Change current directory to webpages.
 * - Make sure database settings file present.
 * - Load DB config.
 *
 * Then check that PatchLog table in database.
 * - If not, assume database not initialized, and offer to load a script.
 *
 * Finally, loop through all .sql files in Upgrade_dbase directory.
 * - If not present, apply patch.
 */
echo "Applying PlanZ Database Patches...\n";
$path = realpath(dirname(__FILE__) . '/../webpages');
chdir($path);

if (!file_exists($path . '/config/db_name.php')) {
    echo "File with db credentials not found: $path/webpages/config/db_name.php \n";
    exit(-1);
}
require_once $path . '/db_functions.php';
if (!prepare_db_and_more()) {
    echo "Could not connect to mysql.\n";
    exit(-1);
}
checkDbaseInitialized($path);
applyDatabasePatches($path);
