<?php
// Copyright (c) 2022 BC Holmes. All rights reserved. See copyright document for more details.
// This function finds the panel list that we want to solicit participant feedback for.

if (!include ('../../config/db_name.php')) {
    include ('../../config/db_name.php');
}
require_once('../db_support_functions.php');
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
try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $modules = PlanzModule::findAll($db);
        header('Content-type: application/json; charset=utf-8');

        $json_string = json_encode(array("modules" => PlanzModule::asJsonArray($modules)));
        echo $json_string;
    } else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        http_response_code(204);
    } else {
        http_response_code(405);
    }
} finally {
    $db->close();
}

?>