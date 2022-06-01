<?php
// Copyright (c) 2022 BC Holmes. All rights reserved. See copyright document for more details.
// This function provides basic management for modules: allowing admins to enable modules.

if (!include ('../../config/db_name.php')) {
    include ('../../config/db_name.php');
}
require_once('../db_support_functions.php');
require_once('../http_session_functions.php');
require_once('../authentication.php');
require_once('../../data_functions.php');

class PlanzModule {
    private $id;
    private $name;
    private $packageName;
    private $description;
    private $isEnabled;

    function __construct($id, $name, $packageName, $description, $isEnabled) {
        $this->id = $id;
        $this->name = $name;
        $this->packageName = $packageName;
        $this->description = $description;
        $this->isEnabled = $isEnabled;
    }

    public static function findAll($db) {
        $query = <<<EOD
        SELECT id, name, package_name, description, is_enabled
             FROM module M
           ORDER BY M.name, M.id;
EOD;        
        $stmt = mysqli_prepare($db, $query);
        return PlanzModule::processSqlStatement($stmt,$query);
    }

    public function updateEnabled($db, $value) {
        $query = <<<EOD
           UPDATE module M
              SET is_enabled = ?
            WHERE id = ?;
EOD;        
        $stmt = mysqli_prepare($db, $query);
        mysqli_stmt_bind_param($stmt, "ii", $value, $this->id);
        if ($stmt->execute()) {
            mysqli_stmt_close($stmt);
        } else {
            throw new DatabaseSqlException("There was a problem with the update: $query");
        }
    }

    public static function findByPackageName($db, $packageName) {
        $query = <<<EOD
        SELECT id, name, package_name, description, is_enabled
             FROM module M
            WHERE package_name = ?
           ORDER BY M.name, M.id;
EOD;        
        $stmt = mysqli_prepare($db, $query);
        mysqli_stmt_bind_param($stmt, "s", $packageName);
        $result = PlanzModule::processSqlStatement($stmt,$query);
        if (count($result) == 1) {
            return $result[0];
        } else {
            return null;
        }
    }

    private static function processSqlStatement($stmt, $query) {
        if (mysqli_stmt_execute($stmt)) {
            $result = [];
            $resultSet = mysqli_stmt_get_result($stmt);
            while ($row = mysqli_fetch_object($resultSet)) {
                $record = new PlanzModule($row->id, $row->name, $row->package_name, $row->description, $row->is_enabled);
                $result[] = $record;
            }
            mysqli_stmt_close($stmt);
            return $result;
        } else {
            throw new DatabaseSqlException("Query could not be executed: $query");
        }        
    }

    function asJson() {
        return array("id" => $this->id, 
            "name" => $this->name,
            "packageName" => $this->packageName,
            "description" => $this->description,
            "isEnabled" => $this->isEnabled ? true : false 
        );
    }

    static function asJsonArray($modules) {
        $result = array();
        foreach ($modules as $m) {
            $result[] = $m->asJson();
        }
        return $result;
    }
}

$db = connect_to_db();
start_session_if_necessary();
$authentication = new Authentication();
try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && $authentication->isAdminModulesAllowed()) {
        $modules = PlanzModule::findAll($db);
        header('Content-type: application/json; charset=utf-8');

        $json_string = json_encode(array("modules" => PlanzModule::asJsonArray($modules)));
        echo $json_string;
    } else if ($_SERVER['REQUEST_METHOD'] === 'POST' && $authentication->isAdminModulesAllowed()) {

        $json_string = file_get_contents('php://input');
        $json = json_decode($json_string, true);
        $db->begin_transaction();
        try {
            foreach ($json as $key => $value) {
                $planzModule = PlanzModule::findByPackageName($db, $key);
                if ($planzModule) {
                    $planzModule->updateEnabled($db, $value ? 1 : 0);
                }
            }
            $db->commit();

            http_response_code(201);
        } catch (Exception $e) {
            $db->rollback();
            throw $e;
        }
        http_response_code(204);
    } else if ($_SERVER['REQUEST_METHOD'] === 'GET' || $_SERVER['REQUEST_METHOD'] === 'POST') {
        http_response_code(401);
    } else {
        http_response_code(405);
    }
} finally {
    $db->close();
}

?>