<?php
// Copyright (c) 2015-2021 Peter Olszowka. All rights reserved. See copyright document for more details.
global $message_error, $title;
$title = "Reports in Category";
require_once('StaffCommonCode.php');
$CON_NAME = CON_NAME;

function sort_by_report_name($r1, $r2) {
    return strcmp($r1['name'], $r2['name']);
}

$reportcategoryid = getString("reportcategory");
if ($reportcategoryid === null)
    $reportcategoryid = "";

$prevErrorLevel = error_reporting();
$tempErrorLevel = $prevErrorLevel & ~ E_WARNING;
error_reporting($tempErrorLevel);
$includeFile = REPORT_INCLUDE_DIRECTORY . 'staffReportsInCategoryInclude.php';
if (!include $includeFile) {
    $message_error = "Report menus not built.  File $includeFile not found.";
    RenderError($message_error);
    exit();
}
error_reporting($prevErrorLevel);
if ($reportcategoryid !== "" && !isset($reportCategories[$reportcategoryid])) {
    $message_error = "Report category $reportcategoryid not found or category has no reports.";
    RenderError($message_error);
    exit();
}
staff_header($title, true);
?>
<div class="container">
    <div class="row mt-2">
        <div class=" col-md-9">
            <div class="list-group">
<?php 
$reportList = array();
if ($reportcategoryid === "") {
    foreach ($reportNames as $reportFileName => $reportName) {
        $reportList[] = array("fileName" => $reportFileName, "name" => $reportName, "description" => $reportDescriptions[$reportFileName]);
    }
} else {
    foreach ($reportCategories[$reportcategoryid] as $reportFileName) {
        $reportList[] = array("fileName" => $reportFileName, "name" => $reportNames[$reportFileName], "description" => $reportDescriptions[$reportFileName]);
    }
}

if (isset($reportOrdering) && $reportOrdering === 'ALPHA') {
    usort($reportList, 'sort_by_report_name');
}

foreach ($reportList as $r => $record) {
    echo "<div class='list-group-item flex-column align-items-start'>\n<h5><a  href='generateReport.php?reportName=" . $record["fileName"] ."'>" . $record["name"] . "</a></h5>\n";
    echo "<div>{$record["description"]}</div>";
    echo "</div>";
}

?>
            </div>
        </div>
    </div>
</div>
<?php 
staff_footer();
?>
