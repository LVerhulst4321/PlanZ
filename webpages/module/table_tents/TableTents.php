<?php
global $participant, $message_error, $message2, $congoinfo, $title, $linki;
$title = "Table Tents";
require_once __DIR__ . '/../../StaffCommonCode.php';
require_once __DIR__ . '/../../schedule_functions.php';

$xml = get_scheduled_events_with_participants_as_xml($linki);
$paramArray = ["basepath" => BASE_PATH];
if (defined('CON_THEME') && CON_THEME !== "") {
    $paramArray['additionalCss'] = CON_THEME;
}
if (array_key_exists("tent-type", $_REQUEST)) {
    $tentType = $_REQUEST["tent-type"];
    $paramArray['tentType'] = mb_strtolower($tentType, "utf-8");
}
if (array_key_exists("paper", $_REQUEST)) {
    $paper = $_REQUEST["paper"];
    $paramArray['paper'] = mb_strtolower($paper, "utf-8");
}
if (array_key_exists("fold-lines", $_REQUEST)) {
    $foldLines = $_REQUEST["fold-lines"];
    $paramArray['foldLines'] = mb_strtolower($foldLines, "utf-8");
}
if (array_key_exists("separator-pages", $_REQUEST)) {
    $separatorPages = $_REQUEST["separator-pages"];
    $paramArray['separatorPages'] = mb_strtolower($separatorPages, "utf-8");
}

RenderXSLT('TableTents.xsl', $paramArray, $xml);
?>
