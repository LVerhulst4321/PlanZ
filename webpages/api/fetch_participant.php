<?php
// Copyright (c) 2022 BC Holmes. All rights reserved. See copyright document for more details.
// This function serves as a REST API to access session history information.

if (!include ('../config/db_name.php')) {
    include ('../config/db_name.php');
}
require_once('./http_session_functions.php');
require_once('./db_support_functions.php');
require_once('../data_functions.php');
require_once('../name.php');

function get_participant($db, $badgeId) {
    $query = <<<EOD
    SELECT
    P.badgeid,
    P.pubsname,
    P.sortedpubsname,
    P.interested,
    P.bio,
    P.htmlbio,
    P.staff_notes,
    CD.firstname,
    CD.lastname,
    CD.badgename,
    CD.phone,
    CD.email,
    CD.postaddress1,
    CD.postaddress2,
    CD.postcity,
    CD.poststate,
    CD.postzip,
    CD.postcountry,
    P.uploadedphotofilename,
    P.approvedphotofilename,
    P.photodenialreasonothertext,
    CASE WHEN ISNULL(P.photouploadstatus) THEN 0 ELSE P.photouploadstatus END AS photouploadstatus,
    R.statustext,
    D.reasontext,
    IFNULL(A.answercount, 0) AS answercount
FROM
                    Participants P
               JOIN CongoDump CD ON P.badgeid = CD.badgeid
    LEFT OUTER JOIN PhotoDenialReasons D USING (photodenialreasonid)
    LEFT OUTER JOIN PhotoUploadStatus R USING (photouploadstatus)
    LEFT JOIN (
        SELECT participantid, COUNT(*) AS answercount
        FROM ParticipantSurveyAnswers
        GROUP BY participantid
    ) A ON (A.participantid = P.badgeid)
WHERE
    P.badgeid = ?
ORDER BY
    CD.lastname,
    CD.firstname
EOD;

    $stmt = mysqli_prepare($db, $query);
    mysqli_stmt_bind_param($stmt, "s", $badgeId);
    $result = null;
    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_object($result)) {
            $name = PersonName::from($row);
            $result = array( 
                "badgeId" => $row->badgeid,
                "name" => $name->asArray(),
                "interested" => $row->interested == 1 ? true : false,
                "phone" => $row->phone,
                "email" => $row->email,
                "bio" => array("text" => $row->bio, "html" => $row->htmlbio),
                "photo" => array(
                    "uploadedFileName" => $row->uploadedphotofilename,
                    "approvedFileName" => $row->approvedphotofilename,
                    "uploadStatus" => array(
                        "status" => $row->photouploadstatus,
                        "statusText" => $row->statustext,
                        "reason" => $row->reasontext,
                        "denialReasonOtherText" => $row->photodenialreasonothertext
                    )
                ),
                "staffNotes" => $row->staff_notes,
                "address" => array(
                    "line1" => $row->postaddress1,
                    "line2" => $row->postaddress2,
                    "city" => $row->postcity,
                    "state" => $row->poststate,
                    "zip" => $row->postzip,
                    "country" => $row->postcountry,
                )
            );
        }
        mysqli_stmt_close($stmt);
        return $result;
    } else {
        throw new DatabaseSqlException("Query could not be executed: $query");
    }
}

start_session_if_necessary();
$db = connect_to_db(true);
try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && isProgrammingStaff()) {
        if (array_key_exists("badgeid", $_REQUEST)) {

            $badgeId = $_REQUEST['badgeid'];
            $participant = get_participant($db, $badgeId);

            header('Content-type: application/json; charset=utf-8');
            $json_string = json_encode(array("participant" => $participant));
            echo $json_string;

        } else {
            http_response_code(400);
        }
    } else if ($_SERVER['REQUEST_METHOD'] === 'GET' && isLoggedIn()) {
        http_response_code(403);
    } else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        http_response_code(401);
    } else {
        http_response_code(405);
    }

} finally {
    $db->close();
}

?>