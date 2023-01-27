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
    (SELECT
    SEH.badgeid as change_by_badgeid,
    CONCAT(CD.firstname, " ", CD.lastname) AS change_by_name,
    CONCAT(SEC.description,
        (CASE WHEN SEH.editdescription IS NOT NULL THEN CONCAT(" — ", SEH.editdescription)
        ELSE ""
        END),
        " — status: ",
        SS.statusname) as description,
    SEH.timestamp as change_ts,
    DATE_FORMAT(SEH.timestamp, "%c/%e/%y %l:%i %p") AS change_ts_format
FROM
         SessionEditHistory SEH
    JOIN SessionEditCodes SEC USING (sessioneditcode)
    JOIN SessionStatuses SS USING (statusid)
    JOIN CongoDump CD ON CD.badgeid = SEH.badgeid
WHERE
    SEH.sessionid=$selsessionid)

UNION

(SELECT
    POSH.change_by_badgeid,
    PartCR.pubsname AS change_by_name,
    (CASE WHEN POSH.change_type = 'insert_assignment' THEN CONCAT('Add ', PartOS.pubsname, ' to session')
            WHEN POSH.change_type = 'remove_assignment' THEN CONCAT('Remove ', PartOS.pubsname, ' from session')
            WHEN POSH.change_type = 'assign_moderator' THEN CONCAT('Assign ', PartOS.pubsname, ' as moderator')
            WHEN POSH.change_type = 'remove_moderator' THEN CONCAT('Unassign ', PartOS.pubsname, ' as moderator')
            ELSE 'Unknown action.' END) as description,
    POSH.change_ts,
    DATE_FORMAT(POSH.change_ts, "%c/%e/%y %l:%i %p") AS change_ts_format
FROM
              participant_on_session_history POSH
         JOIN Participants PartOS ON PartOS.badgeid = POSH.badgeid
         JOIN Participants PartCR ON PartCR.badgeid = POSH.change_by_badgeid
WHERE
    POSH.sessionid=$selsessionid)


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
