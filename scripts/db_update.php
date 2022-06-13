<?php
/**
 * Script for applying PlanZ database patches.
 * Usage:
 *   php scripts/db_update.php
 * (run from base directory of PlanZ).
 * DB settings must be configured before running.
 */


/**
 * This function will take a given $file and execute it directly in php.
 * Adapted from: https://stackoverflow.com/questions/7038739/execute-a-sql-file-using-php/41404203#41404203
 * It tries three methods so it should almost allways work.
 * method 1: Directly via cli using mysql CLI interface. (Best choice)
 * method 2: use mysqli_multi_query
 * method 3: use PDO exec
 * It tries them in that order and checks to make sure they WILL work based on various requirements of those options
 * 
 * @param string $file
 * @return bool
 */
function executeSQL($file)
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
            $execute_command = "\"$mysql\" --host=$db_hostname --user=$db_username --password=$db_password $database < $file_to_execute";
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
        return TRUE;
    }

    //3rd Method Use PDO as command. See http://stackoverflow.com/a/6461110/627473
    //Needs php 5.3, mysqlnd driver
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

        return TRUE;

    }

    return FALSE;
}

/**
 * Return true if specified table exists in database.
 * @param string $tableName
 * @return bool
 */
function checkTableExists($tableName)
{
    global $linki;
    $dbName = DBDB;
    $result = mysqli_query($linki, "SELECT count((1)) as `ct`  FROM INFORMATION_SCHEMA.TABLES where table_schema ='$dbName' and table_name='$tableName';");
    $row = mysqli_fetch_object($result);
    return ($row->ct == 1);
}

/**
 * Get a list of *.sql files in directory.
 * @param string $dir
 * @return array
 */
function getSQLFiles($dir, $ext = 'sql')
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
 * @param string $path
 */
function checkDbaseInitialized($path)
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
            echo "[" . $key + 1 . "] " . $file . "\n";
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
 * @param string $fileName
 * @return bool
 */
function checkPatchApplied($fileName)
{
    global $linki;
    $result = mysqli_query($linki, "SELECT count((1)) as `ct`  FROM PatchLog where patchname='$fileName';");
    $row = mysqli_fetch_object($result);
    return ($row->ct == 1);
}

/**
 * Loop through all patches in Upgrade_dbase directory, and if not already applied, apply to database.
 * @param string $path
 */
function applyDatabasePatches($path)
{
    $files = getSQLFiles($path . '/../Install/Upgrade_dbase', 'sql');
    foreach ($files as $file) {
        if (!checkPatchApplied($file)) {
            echo "Applying patch $file...\n";
            if (!executeSQL($path . '/../Install/Upgrade_dbase/' . $file)) {
                echo "Error applying patch.\n";
                exit(-1);
            }
        }
    }
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
require_once($path . '/db_functions.php');
if (!prepare_db_and_more()) {
    echo "Could not connect to mysql.\n";
    exit(-1);
}
checkDbaseInitialized($path);
applyDatabasePatches($path);
