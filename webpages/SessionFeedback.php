<?php
    // Created by BC Holmes on 2021-12-16
    require ('PartCommonCode.php');

    $title="Session Feedback";
    if (!may_I('SessionFeedback')) {
        $message="You do not currently have permission to view this page.<br />\n";
        RenderError($message);
        exit();
    }
    $query = <<<EOD
SELECT
        P.interested
    FROM
        Participants P
    WHERE
        P.badgeid = '$badgeid';
EOD;
    $results = mysqli_query_with_error_handling($query);
    if ($results) {
        $resultXML = mysql_result_to_XML("participant_info", $results);
        $paramArray = array();

        error_log("Query: $query");

        participant_header($title, false, 'Normal', true);
        RenderXSLT('SessionFeedback.xsl', $paramArray, $resultXML);
        participant_footer();
    }
?>
