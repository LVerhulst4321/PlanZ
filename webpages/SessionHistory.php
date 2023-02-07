<?php
// Copyright (c) 2011-2020 Peter Olszowka. All rights reserved. See copyright document for more details.

global $title;
$title="Session History";
require_once('StaffCommonCode.php');

staff_header($title, true);

$queryArray = array();
if (isset($_POST["selsess"])) {
    $selsessionid=filter_var($_POST["selsess"], FILTER_VALIDATE_INT);
} elseif (isset($_GET["selsess"])) {
    $selsessionid=filter_var($_GET["selsess"], FILTER_VALIDATE_INT);
} else {
    $selsessionid=0; // room was not yet selected.
}

$queryArray["chooseSession"]=<<<EOD
SELECT
        T.trackname,
        S.sessionid,
        S.title
    FROM
             Sessions S
        JOIN Tracks T USING (trackid)
        JOIN SessionStatuses SS USING (statusid)
    WHERE
        1  ##SS.may_be_scheduled = 1
    ORDER BY
        T.trackname, S.sessionid, S.title;
EOD;
if ($selsessionid != 0) {
    $queryArray["title"]=<<<EOD
SELECT
        title
    FROM
        Sessions
    WHERE
        sessionid = $selsessionid;
EOD;

    $queryArray["currentAssignments"]=<<<EOD
SELECT
        COALESCE(POS.moderator, 0) AS moderator,
        P.badgeid,
        P.pubsname,
        P.sortedpubsname
    FROM
             ParticipantOnSession POS
        JOIN Participants P USING (badgeid)
    WHERE
        POS.sessionid=$selsessionid
    ORDER BY
        moderator DESC;
EOD;

    $queryArray["changes"]=<<<EOD
    SELECT
    change_by_badgeid,
    change_by_name,
    description,
    change_ts,
    DATE_FORMAT(change_ts, "%c/%e/%y %l:%i %p") AS change_ts_format
FROM
         session_change_history
WHERE
    sessionid=$selsessionid
ORDER BY change_ts;
EOD;

}

if (($resultXML=mysql_query_XML($queryArray))===false) {
    $message="Error querying database. Unable to continue.<br>";
    echo "<p class\"alert alert-error\">$message</p>\n";
    staff_footer();
    exit();
}

$parametersNode = $resultXML->createElement("parameters");
$docNode = $resultXML->getElementsByTagName("doc")->item(0);
$parametersNode = $docNode->appendChild($parametersNode);
$parametersNode->setAttribute("selsessionid", $selsessionid);
RenderXSLT('SessionHistory.xsl', array(), $resultXML);
staff_footer();
?>
