<?php
// Copyright (c) 2020-2021 Peter Olszowka. All rights reserved. See copyright document for more details.
// File created by Syd Weinstein on 2020-09-03
global $message_error, $title, $linki, $session;
$bootstrap4 = true;
$title = "Edit Configuration Tables";
require_once('StaffCommonCode.php');

$paramArray = array();
staff_header($title, $bootstrap4);
$editAnyTable = 
       may_I('ce_AgeRanges')
    || may_I('ce_All')
    || may_I('ce_BioEditStatuses')
    || may_I('ce_Credentials')
    || may_I('ce_Divisions')
    || may_I('ce_EmailCC')
    || may_I('ce_EmailFrom')
    || may_I('ce_EmailTo')
    || may_I('ce_Features')
    || may_I('ce_Interests')
    || may_I('ce_KidsCategories')
    || may_I('ce_LanguageStatuses')
    || may_I('ce_Locations')
    || may_I('ce_PhotoDenialReasons')
    || may_I('ce_Pronouns')
    || may_I('ce_PubStatuses')
    || may_I('ce_RegTypes')
    || may_I('ce_Roles')
    || may_I('ce_RoomColors')
    || may_I('ce_RoomHasSet')
    || may_I('ce_Rooms')
    || may_I('ce_RoomSets')
    || may_I('ce_room_report_group')
    || may_I('ce_room_report_group_has_room')
    || may_I('ce_Services')
    || may_I('ce_ServiceTypes')
    || may_I('ce_SessionStatuses')
    || may_I('ce_TechLevels')
    || may_I('ce_Tags')
    || may_I('ce_Times')
    || may_I('ce_Tracks')
    || may_I('ce_Types');
if (!$editAnyTable) {
    $message_error = "You do not currently have permission to view this page.<br>\n";
    StaffRenderErrorPage($title, $message_error, true);
    exit();
}

$PriorArray["getSessionID"] = session_id();

$ControlStrArray = generateControlString($PriorArray);
$paramArray["control"] = $ControlStrArray["control"];
$paramArray["controliv"] = $ControlStrArray["controliv"];
$xmlDoc = GeneratePermissionSetXML();
// echo(mb_ereg_replace("<(query|row)([^>]*/[ ]*)>", "<\\1\\2></\\1>", $xmlDoc->saveXML(), "i"));
RenderXSLT('ConfigTableEditor.xsl', $paramArray, $xmlDoc);

staff_footer();
?>