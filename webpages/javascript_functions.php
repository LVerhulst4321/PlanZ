<?php
//	Copyright (c) 2011-2021 Peter Olszowka. All rights reserved. See copyright document for more details.
function load_external_javascript($isDataTables = false, $isTurnstile = false, $bootstrap4 = false) {
    if ($bootstrap4) { ?>
    <script src="<?php echo BASE_PATH; ?>external/jquery3.5.1/jquery-3.5.1.min.js"></script>
    <script src="<?php echo BASE_PATH; ?>external/bootstrap4.5.0/bootstrap.bundle.min.js" type="text/javascript"></script>
<?php } else { ?>
    <script src="<?php echo BASE_PATH; ?>external/jquery1.7.2/jquery-1.7.2.min.js"></script>
    <script src="<?php echo BASE_PATH; ?>external/jqueryui1.8.16/jquery-ui-1.8.16.custom.min.js"></script>
    <script src="<?php echo BASE_PATH; ?>external/bootstrap2.3.2/bootstrap.js" type="text/javascript"></script>
<?php } ?>
    <script src="<?php echo BASE_PATH; ?>external/choices9.0.0/choices.min.js"></script>
<?php if ($isDataTables && $bootstrap4) { ?>
    <script src="//cdn.datatables.net/1.10.25/js/jquery.dataTables.min.js"></script>
<?php } else if ($isDataTables) { ?>
    <script src="<?php echo BASE_PATH; ?>external/dataTables1.10.16/jquery.dataTables.js"></script>
<?php }
    if ($isTurnstile) { ?>
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
<?php }
}

function load_internal_javascript($title, $isDataTables = false) {
    ?>
    <script src="<?php echo BASE_PATH; ?>js/main.js"></script>
    <?php
    /**
     * These js files initialize themselves and therefore should be included only on the relevant pages.
     * See main.js
     *
     * Invite Participants -- InviteParticipants.js
     * Maintain Room Schedule -- MaintainRoomSched.js
     * Reset Password -- ForgotPasswordResetForm.js
     * Session History -- SessionHistory.js
     *
     * Other js files may be included in this switch statement, but aren't required
     */
    switch ($title) {
        case "Invite Participants":
            echo "<script src=\"".BASE_PATH."js/InviteParticipants.js\"></script>\n";
            break;
        case "Maintain Room Schedule":
            echo "<script src=\"".BASE_PATH."js/MaintainRoomSched.js\"></script>\n";
            break;
        case "Reset Password":
            echo "<script src=\"".BASE_PATH."js/ForgotPasswordResetForm.js\"></script>\n";
            break;
        case "Session History":
            echo "<script src=\"".BASE_PATH."js/SessionHistory.js\"></script>\n";
            break;
        case "Administer Phases":
            echo "<script src=\"".BASE_PATH."js/AdminPhases.js\"></script>\n";
            break;
        case "Edit Custom Text":
            echo "<script src=\"".BASE_PATH."external/tinymce-5.6.2/js/tinymce/tinymce.min.js\"></script>\n";
            echo "<script src=\"".BASE_PATH."js/EditCustomText.js\"></script>\n";
            break;
        case "Edit Survey":
            echo "<script src=\"".BASE_PATH."external/tabulator-4.9.3/js/tabulator.js\"></script>\n";
            echo "<script src=\"".BASE_PATH."js/EditSurvey.js\"></script>\n";
            echo "<script src=\"".BASE_PATH."js/RenderSurvey.js\"></script>\n";
            echo "<script src=\"".BASE_PATH."external/tinymce-5.6.2/js/tinymce/tinymce.min.js\"></script>\n";
            break;
        case "Participant Survey":
            echo "<script src=\"".BASE_PATH."js/PartSurvey.js\"></script>\n";
            echo "<script src=\"".BASE_PATH."js/RenderSurvey.js\"></script>\n";
            echo "<script src=\"".BASE_PATH."external/tinymce-5.6.2/js/tinymce/tinymce.min.js\"></script>\n";
        case "Preview Survey":
            echo "<script src=\"".BASE_PATH."js/RenderSurvey.js\"></script>\n";
            echo "<script src=\"".BASE_PATH."external/tinymce-5.6.2/js/tinymce/tinymce.min.js\"></script>\n";
            break;
        case "Session Search Results":
            echo "<script src=\"".BASE_PATH."js/PartSearchSessionsSubmit.js\"></script>\n";
            break;
        case "Administer Participants":
            echo "<script src=\"".BASE_PATH."external/tinymce-5.6.2/js/tinymce/tinymce.min.js\"></script>\n";
            echo "<script src=\"".BASE_PATH."js/AdminParticipants.js\"></script>\n";
            break;
        case "Administer Photos":
            echo "<script src=\"".BASE_PATH."external/croppie.2.6.5/croppie.min.js\"></script>\n";
            echo "<script src=\"".BASE_PATH."js/AdminPhotos.js\"></script>\n";
            break;
        case "My Profile":
            echo "<script src=\"".BASE_PATH."external/tinymce-5.6.2/js/tinymce/tinymce.min.js\"></script>\n";
            echo "<script src=\"".BASE_PATH."js/myProfile.js\"></script>";
            break;
        case "My Photo":
            echo "<script src=\"".BASE_PATH."external/croppie.2.6.5/croppie.min.js\"></script>\n";
            echo "<script src=\"".BASE_PATH."js/myPhoto.js\"></script>";
            break;
        case "Edit Session":
            echo "<script src=\"".BASE_PATH."external/tinymce-5.6.2/js/tinymce/tinymce.min.js\"></script>\n";
            echo "<script src=\"".BASE_PATH."js/editCreateSession.js\"></script>\n";
            break;
        case "Create New Session":
            echo "<script src=\"".BASE_PATH."external/tinymce-5.6.2/js/tinymce/tinymce.min.js\"></script>\n";
            echo "<script src=\"".BASE_PATH."js/editCreateSession.js\"></script>\n";
            break;
        case "Edit Configuration Tables":
            echo "<script src=\"".BASE_PATH."external/tinymce-5.6.2/js/tinymce/tinymce.min.js\"></script>\n";
            echo "<script src=\"".BASE_PATH."external/tabulator-4.9.3/js/tabulator.js\"></script>\n";
            echo "<script src=\"".BASE_PATH."js/EditConfigTables.js\"></script>\n";
            break;
        case "Table Tents":
            echo "<script src=\"".BASE_PATH."js/tabletents.js\"></script>\n";
            break;
        default:
            if ($isDataTables) {
                echo "<script src=\"".BASE_PATH."js/Reports.js\"></script>\n";
            }
    }
?>
<script src="<?php echo BASE_PATH; ?>js/staffMaintainSchedule.js"></script>
<script src="<?php echo BASE_PATH; ?>js/partPanelInterests.js"></script>
<?php
}
?>
