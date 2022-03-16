<?php
// Created by BC Holmes on 2022-01-29;

global $linki, $title;
$title = "Create New Account";
require ('PartCommonCode.php');

function find_original_request($db, $selector) {

    if (DB_DEFAULT_TIMEZONE != "") {
        $query = "SET time_zone = '" . DB_DEFAULT_TIMEZONE . "';";
        if (!mysqli_query($db, $query)) {
            throw new DatabaseSqlException("Could not process timezone change: $query");
        }
    }

    $query=<<<EOD
    SELECT
            token
        FROM
            ParticipantPasswordResetRequests
        WHERE badgeidentered = ''
        AND selector = ?
        AND cancelled = 0
        AND NOW() < expirationdatetime;
EOD;
    $validator = null;
    $stmt = mysqli_prepare($db, $query);
    mysqli_stmt_bind_param($stmt, "s", $selector);
    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);

        while ($row = mysqli_fetch_object($result)) {
            $validator = $row->token;
        }
        $result->free_result();
        $stmt->close();
        
        return $validator;
    } else {
        throw new DatabaseSqlException("The query could not be processed");
    }
}



function create_hidden_parameters($selector, $validator) {
    $controlParams = array(
        "selector" => $selector,
        "validator" => $validator
    );
    $controlArray = generateControlString($controlParams);
    $params = array(
        "control" => $controlArray['control'],
        "controliv" => $controlArray['controliv']
    );
    return $params;    
}


$selector = getString('selector');
$validator = getString('validator');

participant_header($title, true, 'Login', true);

if (hash('sha256', hex2bin($validator)) === find_original_request($linki, $selector)) {
    RenderXSLT('CreateNewAccountLink.xsl', create_hidden_parameters($selector, $validator));
} else {
    RenderXSLT('ForgotPasswordBadLink.xsl', array());
}
participant_footer();
?>