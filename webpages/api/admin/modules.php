<?php

namespace PlanZ;

// Copyright (c) 2022 BC Holmes. All rights reserved. See copyright document for more details.
// This function provides basic management for modules: allowing admins to enable modules.

if (file_exists(__DIR__ . '/../../config/db_name.php')) {
    include __DIR__ . '/../../config/db_name.php';
}
require_once('../../db_exceptions.php');
require_once('../../login_functions.php');
require_once('../db_support_functions.php');
require_once('../authentication.php');
require_once('../../data_functions.php');
require_once('../http_session_functions.php');
require_once('./module_model.php');

use Authentication;
use Exception;

function update_current_modules_and_permissions($db, $badgeId) {
    set_permission_set($badgeId, $db);
    set_modules($db);
}

start_session_if_necessary();
$db = connect_to_db(true);
$authentication = new Authentication();
try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && $authentication->isAdminModulesAllowed()) {
        $modules = PlanZModule::findAll($db);
        header('Content-type: application/json; charset=utf-8');

        $json_string = json_encode(array("modules" => PlanZModule::asJsonArray($modules)));
        echo $json_string;
    } else if ($_SERVER['REQUEST_METHOD'] === 'POST' && $authentication->isAdminModulesAllowed()) {

        $json_string = file_get_contents('php://input');
        $json = json_decode($json_string, true);
        $db->begin_transaction();
        try {
            foreach ($json as $key => $value) {
                $planzModule = PlanZModule::findByPackageName($db, $key);
                if ($planzModule) {
                    $planzModule->updateEnabled($db, $value ? 1 : 0);
                }
            }
            $db->commit();

        } catch (Exception $e) {
            $db->rollback();
            throw $e;
        }

        update_current_modules_and_permissions($db, $authentication->getBadgeId());

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
