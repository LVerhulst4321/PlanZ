<?php
global $returnAjaxErrors, $return500errors, $title;
$title = "Submit Report Favourite";
$returnAjaxErrors = true;
$return500errors = true;
require_once('CommonCode.php');
require_once('report_favourites_functions.php');

function respond_json($statusCode, $severity, $text) {
    http_response_code($statusCode);
    header('Content-type: application/json');
    echo json_encode(array("severity" => $severity, "text" => $text));
    exit();
}

if (!isLoggedIn() || !may_I('Staff')) {
    respond_json(401, "danger", "You do not have access to perform this function. Perhaps your session timed out?");
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond_json(405, "danger", "Method not allowed.");
}

$json = json_decode(file_get_contents('php://input'), true);
if (!is_array($json) || !isset($json['reportName']) || !is_string($json['reportName'])
        || !isset($json['favourite']) || !is_bool($json['favourite'])) {
    respond_json(400, "danger", "There's something wrong with your request.");
}
$reportName = $json['reportName'];

$includeFile = REPORT_INCLUDE_DIRECTORY . 'staffReportsInCategoryInclude.php';
if (!file_exists($includeFile)) {
    respond_json(409, "danger", "Report menus not built.");
}
$prevErrorLevel = error_reporting();
error_reporting($prevErrorLevel & ~ E_WARNING);
include $includeFile;
error_reporting($prevErrorLevel);
if (!isset($reportItem[$reportName])) {
    respond_json(400, "danger", "Unknown report.");
}

set_report_favourite($_SESSION['badgeid'], $reportName, $json['favourite']);
header('Content-type: application/json');
echo json_encode(array("reportName" => $reportName, "favourite" => $json['favourite']));
?>
