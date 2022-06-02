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
require_once('./module_model.php');

$db = connect_to_db(true);
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