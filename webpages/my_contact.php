<?php
// Copyright (c) 2011-2021 Peter Olszowka. All rights reserved. See copyright document for more details.
global $participant, $message, $message_error, $message2, $congoinfo, $title;
$title="My Profile";
require ('PartCommonCode.php'); // initialize db; check login;
//                                  set $badgeid from session
$regTypeField = USE_REGTYPE_DESCRIPTION ? "COALESCE(RT.message, CD.regtype) AS regtype" : "CD.regtype";
$queryArray["participant_info"] = <<<EOD
SELECT
        CD.badgeid, CD.firstname, CD.lastname, CD.badgename, CD.phone, CD.email,
        CD.postaddress1, CD.postaddress2, CD.postcity, CD.poststate, CD.postzip,
        CD.postcountry, $regTypeField, P.pubsname, P.sortedpubsname, P.password, P.bestway, P.interested, P.bio,
        P.htmlbio, P.share_email, P.use_photo, P.allow_streaming, P.allow_recording, PRO.pronounname, P.approvedphotofilename,
        P.anonymous
    FROM
       CongoDump CD
       JOIN Participants P USING (badgeid)
       LEFT JOIN ParticipantDetails PD USING (badgeid)
       LEFT JOIN Pronouns PRO USING (pronounid)
       LEFT JOIN RegTypes RT USING (regtype)
    WHERE
        CD.badgeid=?;
EOD;
$param_array["participant_info"] = array($badgeid);
$type_array["participant_info"] = "s";
$queryArray["credentials"] = <<<EOD
SELECT
        CR.credentialid, CR.credentialname, CR.display_order, PHC.badgeid
    FROM
            Credentials CR
       LEFT JOIN ParticipantHasCredential PHC ON CR.credentialid = PHC.credentialid
            AND PHC.badgeid=?;
EOD;
$param_array["credentials"] = array($badgeid);
$type_array["credentials"] = "s";

if (($resultXML=mysql_prepare_query_XML($queryArray, $type_array, $param_array))===false) {
    RenderError($message_error);
    exit();
}
$paramArray = array();
$paramArray['conName'] = CON_NAME;
$paramArray['enableShareEmailQuestion'] = ENABLE_SHARE_EMAIL_QUESTION ? 1 : 0;
$paramArray['enableUsePhotoQuestion'] = ENABLE_USE_PHOTO_QUESTION ? 1 : 0;
$paramArray['enableStreamingQuestion'] = defined('ENABLE_ALLOW_STREAMING_QUESTION') && ENABLE_ALLOW_STREAMING_QUESTION ? 1 : 0;
$paramArray['enableRecordingQuestion'] = defined('ENABLE_ALLOW_RECORDING_QUESTION') && ENABLE_ALLOW_RECORDING_QUESTION ? 1 : 0;
$paramArray['enableBestwayQuestion'] = ENABLE_BESTWAY_QUESTION ? 1 : 0;
$paramArray['useRegSystem'] = USE_REG_SYSTEM ? 1 : 0;
$paramArray['maxBioLen'] = MAX_BIO_LEN;
$paramArray['enablePronouns'] = (defined('USE_PRONOUNS') && USE_PRONOUNS) ? 1 : 0;
$paramArray['enableBioEdit'] = may_I('EditBio');
$paramArray['htmlbio'] = HTML_BIO ? 1 : 0;
$paramArray['userIdPrompt'] = USER_ID_PROMPT;
$paramArray["RESET_PASSWORD_SELF"] = RESET_PASSWORD_SELF;
if (defined("PARTICIPANT_PHOTOS") && PARTICIPANT_PHOTOS && defined("PHOTO_PUBLIC_DIRECTORY")) {
	$paramArray['photoPath'] = PHOTO_PUBLIC_DIRECTORY;
	if (defined("PHOTO_DEFAULT_IMAGE") && PHOTO_DEFAULT_IMAGE) {
		$paramArray['defaultPhoto'] = PHOTO_DEFAULT_IMAGE;
	}
}
participant_header($title, false, 'Normal', true);
$resultXML = appendCustomTextArrayToXML($resultXML);
RenderXSLT('my_profile.xsl', $paramArray, $resultXML);
participant_footer();
?>
