<?php
// Copyright (c) 2015-2021 Peter Olszowka. All rights reserved. See copyright document for more details.
global $message_error, $title, $linki;
$title = "Reports in Category";
require_once('StaffCommonCode.php');
require_once('report_favourites_functions.php');
$CON_NAME = CON_NAME;

function sort_by_report_name($r1, $r2) {
    return strcmp($r1['name'], $r2['name']);
}

$reportcategoryid = getString("reportcategory");
if ($reportcategoryid === null)
    $reportcategoryid = "";
$showFavourites = getInt("favourites") === 1;
if ($showFavourites) {
    $title = "Favourite Reports";
}

$prevErrorLevel = error_reporting();
$tempErrorLevel = $prevErrorLevel & ~ E_WARNING;
error_reporting($tempErrorLevel);
$includeFile = REPORT_INCLUDE_DIRECTORY . 'staffReportsInCategoryInclude.php';
if (file_exists($includeFile)) {
    include $includeFile;
} else {
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

$modules = $_SESSION['modules'];
$favourites = get_report_favourites($_SESSION['badgeid']);

staff_header($title, true);
?>
<div class="container">
    <div class="row mt-2">
        <div class=" col-md-9">
            <div class="list-group">
<?php
$reportList = array();
foreach ($reportItem as $reportFileName => $item) {
    if ($showFavourites) {
        $inList = isset($favourites[$reportFileName]);
    } else {
        $inList = $reportcategoryid === "" || in_array($reportcategoryid, $item['categories']);
    }
    if ($inList) {
        if (!array_key_exists('module', $item) || in_array($item['module'], $modules)) {
            $reportList[] = array("fileName" => $reportFileName, "name" => $item['name'], "description" => $item['description']);
        }
    }
}

if ($showFavourites || (isset($reportOrdering) && $reportOrdering === 'ALPHA')) {
    usort($reportList, 'sort_by_report_name');
}

if ($showFavourites && count($reportList) === 0) {
    echo "<div class='list-group-item'>No favourites yet. Click <i class='bi bi-star'></i> next to a report to add it.</div>\n";
}

foreach ($reportList as $r => $record) {
    echo "<div class='list-group-item flex-column align-items-start'>\n<h5 class='d-flex align-items-center'>" . render_report_favourite_button($record["fileName"], isset($favourites[$record["fileName"]]))
        . "<a  href='generateReport.php?reportName=" . $record["fileName"] ."'>" . $record["name"] . "</a></h5>\n";
    echo "<div>{$record["description"]}</div>";
    echo "</div>";
}

?>
            </div>
        </div>
    </div>
</div>
<?php
render_report_favourite_scripts();
staff_footer();
?>
