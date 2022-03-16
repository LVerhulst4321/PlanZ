<?php
//	Copyright (c) 2011-2021 Peter Olszowka. All rights reserved. See copyright document for more details.
require_once ('StaffCommonCode.php');
require_once ('RenderSearchSessionResults.php');
$trackid = getInt("track", "");
$typeid = getInt("type", "");
$statusid = getInt("status", "");
$sessionid = getInt("sessionid", "");
$divisionid = getInt("divisionid", "");
$searchTitle = getString("searchtitle");
$hashtag = getString("hashtag");
$csv = getString("csv");
if ($searchTitle === NULL) {
    $searchTitle = "";
}
$encTitle = urlencode($searchTitle);
$tags = getArrayOfInts("tags", array());
$pubstatusid = getInt("pubstatusid", "");

$_SESSION['return_to_page'] = "ShowSessions.php?status=$statusid&track=$trackid&type=$typeid&sessionid=$sessionid";
$_SESSION['return_to_page'] .= "&divisionid=$divisionid&searchtitle=$encTitle&pubstatusid=$pubstatusid";

$sessionSearchArray = array();
$sessionSearchArray['trackidList'] = strval($trackid);
$sessionSearchArray['typeidList'] = strval($typeid);
$sessionSearchArray['statusidList'] = strval($statusid);
$sessionSearchArray['sessionid'] = strval($sessionid);
$sessionSearchArray['divisionid'] = strval($divisionid);
$sessionSearchArray['searchTitle'] = $searchTitle;
$sessionSearchArray['hashtag'] = $hashtag;
$sessionSearchArray['tagmatch'] = getString("tagmatch");
$sessionSearchArray['tagidArray'] = $tags;
$sessionSearchArray['pubstatusid'] = strval($pubstatusid);

if ($csv === "csv") {

    require_once('retrieve.php');
    require_once('render_functions.php');
    require_once('csv_report_functions.php');
    // retrieveSessions() will exit on error
    if ($result = retrieveSessions($sessionSearchArray)) {
        $showlinks = false;

        header("Content-disposition: attachment; filename=sessions-".date(DATE_ISO8601).".csv");
        header('Content-type: text/csv; charset=utf-8');

        $html = RenderPrecisToString($result, $showlinks, $href, $sessionSearchArray, true);
        render_html_table_as_csv($html);
    }

} else {
    RenderSearchSessionResults($sessionSearchArray, "./ShowSessions.php");
}
?>
