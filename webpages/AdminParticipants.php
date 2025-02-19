<?php
//	Copyright (c) 2011-2021 Peter Olszowka. All rights reserved. See copyright document for more details.
global $title;
$title = "Administer Participants";
$bootstrap4 = true;
require_once('StaffCommonCode.php');
$fbadgeid = getInt("badgeid");
staff_header($title, $bootstrap4);
if ($fbadgeid) {
    echo "<script type=\"text/javascript\">fbadgeid = $fbadgeid;</script>\n";
}
$allowEditPermission = may_I('EditUserPermissions') ? 'yes' : 'no';
?>
<form id="adminParticipantsForm">
<div class="card">
    <div class="card-body">
    <div id="resultBoxDIV"><span class="beforeResult" id="resultBoxSPAN">Result messages will appear here.</span></div>
    <div id="searchPartsDIV">
        <div class="row">
            <div class="col-sm-12">
                <div class="dialog">Enter all or part of first name, last name, badge name, <span style="font-weight:bold">or</span> published name.  If you enter numbers, it will be interpreted as a complete <?php echo USER_ID_PROMPT; ?>.
                </div>
            </div>
        </div>
        <div class="row" style="margin-top: 0.5em">
            <div class="col-sm-3">
                <input class="form-control" type="text" id="searchPartsINPUT" onkeypress = "if (event.keyCode === 13) doSearchPartsBUTN();" />
                <input type="hidden" id="searchPhotoApproval" value=""/>
            </div>
            <div class="col-sm-9">
                <div role="group" aria-label="search actions">
                    <button type="button" class="btn btn-primary" data-loading-text="Searching..." id="searchPartsBUTN" style="margin-right:10px;">Search</button>
                    <button type="button" class="btn btn-secondary" id="prevSearchResultBUTN" style="display: none; margin-right:10px;" disabled onclick="prevParticipant();">Previous</button>
                    <button type="button" class="btn btn-secondary" id="nextSearchResultBUTN" style="display: none; margin-right:10px;" disabled onclick="nextParticipant();">Next</button>
                    <button type="button" class="btn btn-secondary" id="toggleSearchResultsBUTN"><span id="toggleText">Hide</span> Results</button>
                </div>
            </div>
        </div>
        <input type="hidden" id="allow-edit-permission" value="<?= $allowEditPermission ?>" />
        <div style="margin-top: 1em; height:250px; overflow:auto; border: 1px solid grey" id="searchResultsDIV">&nbsp;
        </div>
    </div>
    <div id="unsavedWarningModal" class="modal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Data Not Saved</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>You have unsaved changes for <span id='warnName'></span>, <?php echo USER_ID_PROMPT; ?>: <span id='warnNewBadgeID'></span>!</p>
                </div>
                <div class="modal-footer">
                    <button type="button" id="cancelOpenSearchBUTN" class="btn btn-primary" data-dismiss="modal">Cancel</button>
                    <button type="button" id="overrideOpenSearchBUTN" class="btn btn-secondary" onclick="return loadNewParticipant();" >Discard changes</button>
                </div>
            </div>
        </div>
    </div>
</div>
</div>

<div id="resultsDiv" class="mt-3">
    <div class="card">
        <div class="card-body">


        <div class="row">
            <div class="col-sm-2 col-xl-1">
                <div class="form-group">
                    <label for="badgeid" class="mb-1"><?php echo USER_ID_PROMPT; ?>:</label>
                    <input class="form-control disabled" id="badgeid" type="text" readonly="readonly" />
                </div>
            </div>
            <div class="col-sm-10 col-xl-11">
                <div class="row">
<?php
if (USE_REG_SYSTEM === TRUE) {
?>
                    <div class="col-sm-4">
                        <div class="form-group">
                            <label for="lname_fname" class="mb-1">Last name, first name:</label>
                            <input class="form-control disabled" id="lname_fname" type="text" readonly="readonly" style="max-width:20rem;" />
                        </div>
                    </div>
<?php
} else {
?>
                    <div class="col-sm-4">
                        <div class="form-group">
                            <label for="lastname" class="mb-1">Last name:</label>
                            <input class="form-control mycontrol" id="lastname" type="text" maxlength="40" />
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="form-group">
                            <label for="firstname" class="mb-1">First name:</label>
                            <input class="form-control mycontrol" id="firstname" type="text" maxlength="35" />
                        </div>
                    </div>
<?php
};
?>
                    <div class="col-sm-4">
                        <div class="form-group">
                            <label for="badgename" class="mb-1">Badge name:</label>
<?php
if (USE_REG_SYSTEM === TRUE) {
?>
                            <input type="text" id="badgename" class="form-control disabled" readonly="readonly" maxlength="50" />
<?php
} else {
?>
                            <input type="text" id="badgename" class="form-control mycontrol" maxlength="50" />
<?php
}
?>
                        </div>
                    </div>
                </div>
            </div>
        </div>


        <div class="row">
            <div class="col-sm-2 col-xl-1 text-center">
                <img id="participantAvatar" class="rounded-circle participant-avatar participant-avatar-sm img-thumbnail d-none d-sm-inline"
                    src=<?php echo '"' . PHOTO_PUBLIC_DIRECTORY . '/' . PHOTO_DEFAULT_IMAGE . '"'; ?>
                    data-default=<?php echo '"' . PHOTO_PUBLIC_DIRECTORY . '/' . PHOTO_DEFAULT_IMAGE . '"'; ?>
                    alt="Avatar"
                    data-basedir=<?php echo '"' . PHOTO_PUBLIC_DIRECTORY . '/"'; ?> />
            </div>
<?php
if (USE_REG_SYSTEM === FALSE) {
?>
            <div class="col-sm-10 col-xl-11">
                <div class="row">
            <div class="col-sm-2">
                <div class="form-group">
                    <label for="phone" class="mb-1">Phone number:</label>
                    <input class="form-control mycontrol" id="phone" type="text" maxlength="100" />
                </div>
            </div>
            <div class="col-sm-2">
                <div class="form-group">
                    <label for="email" class="mb-1">Email address:</label>
                    <input class="form-control mycontrol" id="email" type="text" maxlength="100" />
                </div>
            </div>
<?php
}
?>
            <div class="col-sm-4">
                <div class="form-group">
                    <label for="pubsname" class="mb-1">Name for publications:</label>
                    <input class="form-control mycontrol" id="pubsname" type="text" readonly="readonly" maxlength="50" />
                </div>
            </div>
            <div class="col-sm-4">
                <div class="form-group">
                    <label for="sortedpubsname" class="mb-1">Sorted Name for publications:</label>
                    <input class="form-control mycontrol" id="sortedpubsname" type="text" readonly="readonly" maxlength="50" />
                </div>
            </div>
        </div>


<?php
if (USE_REG_SYSTEM === FALSE) {
?>
        <div class="row">
            <div class="col-sm-4">
                <div class="form-group">
                    <label for="postaddress1" class="mb-1">Postal Address:</label>
                    <input class="form-control mycontrol" id="postaddress1" type="text" maxlength="100" />
                </div>
            </div>
            <div class="col-sm-4">
                <div class="form-group">
                    <label for="postaddress2" class="mb-1">Postal Address Line 2:</label>
                    <input class="form-control mycontrol" id="postaddress2" type="text" maxlength="100" />
                </div>
            </div>
        </div>


        <div class="row">
            <div class="col-sm-4">
                <div class="form-group">
                    <label for="postcity" class="mb-1">City:</label>
                    <input class="form-control mycontrol" id="postcity" type="text" maxlength="50" />
                </div>
            </div>
            <div class="col-sm-2">
                <div class="form-group">
                    <label for="poststate" class="mb-1">State:</label>
                    <input class="form-control mycontrol" id="poststate" type="text" maxlength="25" />
                </div>
            </div>
            <div class="col-sm-2">
                <div class="form-group">
                    <label for="postzip" class="mb-1">Zip Code:</label>
                    <input class="form-control mycontrol" id="postzip" type="text" maxlength="10" />
                </div>
            </div>
            <div class="col-sm-4">
                <div class="form-group">
                    <label for="postcountry" class="mb-1">Country:</label>
                    <input class="form-control mycontrol" id="postcountry" type="text" maxlength="25" />
                </div>
            </div>
        </div>
        </div>
    </div>
<?php
};

    function yesNoSelectBlock(string $id, string $label, string $help_text = '', bool $display = true) {
        if ($display) {
?>
            <div class="form-group">
                <label for="<?= $id ?>" class="control-label"><?= $label ?></label>
                <select id="<?= $id ?>" class="yesno mycontrol form-control" disabled="disabled" style="width: auto;">
                    <option value="0" selected="selected">&nbsp</option>
                    <option value="1">Yes</option>
                    <option value="2">No</option>
                </select>
<?php
            if ($help_text) {
?>
            <p class="help-block"><?= $help_text ?></p>
<?php
            }
?>
        </div>
<?php
        }
        else {
?>
            <input type="hidden" id="<?= $id ?>" />
<?php
        }
    }
?>

        <div class="container-sm py-2 px-3 my-3 border border-dark rounded">
            <p id="permission-disabled"><strong>Note:</strong> You are not allowed to edit this section.</p>
<?php
    yesNoSelectBlock(
        'interested',
        'Participant is interested and available to participate in ' . CON_NAME . ' programming',
        'Changing this to <em>No</em> will remove the participant from all sessions.',
    );
    yesNoSelectBlock(
        'share_email',
        'Participant gives permission to share email with other participants',
        '',
        ENABLE_SHARE_EMAIL_QUESTION,
    );
    yesNoSelectBlock(
        'use_photo',
        'Participant gives permission to use photos in promotion of the convention',
        '',
        ENABLE_USE_PHOTO_QUESTION,
    );
    yesNoSelectBlock(
        'allow_streaming',
        'Participant gives permission to live stream sessions',
        '',
        ENABLE_ALLOW_STREAMING_QUESTION,
    );
    yesNoSelectBlock(
        'allow_recording',
        'Participant gives permission to record sessions and make available for catchup',
        '',
        ENABLE_ALLOW_RECORDING_QUESTION,
    );
?>
        </div>

<?php
if (RESET_PASSWORD == true) {
    if (may_I("ResetUserPassword")) {
?>
        <div class="container-sm py-2 px-3 my-3 border border-dark rounded">
            <div class="row">
                <div class="col-sm-3">
                    <div class="form-group">
                        <label for="password">Change Participant's Password:</label>
                        <input type="password" maxlength="40" id="password" readonly="readonly" class="form-control mycontrol" />
                    </div>
                    <span id="passwordsDontMatch" style="color: red;">Passwords don't match.</span>
                </div>
            </div>
            <div class="row">
                <div class="col-sm-3">
                    <div class="form-group">
                        <label for="cpassword">Confirm New Password:</label>
                        <input type="password" maxlength="40" id="cpassword" readonly="readonly" class="form-control mycontrol" />
                    </div>
                </div>
            </div>
        </div>
<?php
    };
};
?>
        <div class="container-sm py-2 px-3 my-3 border border-dark rounded">
            <div class="">
                <label for="regtype">Registration type:</label>
            </div>
            <div id="regtype">
            </div>
        </div>

        <div class="row mt-3">
            <div class="col-sm-6">

<?php
if (HTML_BIO === TRUE) {
?>
              <div class="form-group">
                <label for="htmlbio" class="">Participant biography:</label>
                <textarea class="form-control" id="htmlbio" rows="4" cols="80" readonly="readonly" maxlength="<?php echo MAX_BIO_LEN?>" onchange="textChange('htmlbio');" onkeyup="textChange('htmlbio');"></textarea>
              </div>
<?php
} else {
?>
              <div class="form-group">
                <label for="bio" class="">Participant biography:</label>
                <textarea class="form-control mycontrol" id="bio" rows="4" cols="80" data-maxlength="<?php echo MAX_BIO_LEN?>"></textarea>
              </div>

<?php
}
?>
            </div>
            <div class="col-sm-6">
                <div class="form-group">
                    <label for="staffnotes" class="">Staff notes re. participant:</label>
                    <textarea class="form-control mycontrol" id="staffnotes" rows="6" cols="80" readonly="readonly"></textarea>
                </div>
<?php
if (HTML_BIO === TRUE) {
?>
              <div class="form-group">
                 <label for="bio" class="newformlabel">Text biography (updates only after Update is pressed):</label>
                  <textarea class="form-control" id="bio" rows="8" cols="80" readonly="readonly" maxlength="<?php echo MAX_BIO_LEN?>"></textarea>
              </div>
<?php
}
?>
            </div>
        </div>


        <div class="row mt-3">
            <div class="col-sm-4">
                <div class="pb-1">
                    User Permission Roles:
                </div>
                <div>
                    <div class="tag-chk-container" id="role-container">
                    </div>
                </div>
            </div>
        </div>


        <div class="row mt-3">
            <div class="col col-auto">
                <button type="button" class="btn btn-primary" data-loading-text="Updating..." id="updateBUTN"
                    onclick="updateBUTTON();" disabled="disabled">Update
                </button>
            </div>
            <div class="col col-auto" id="showsurveydiv" >
                <button type="button" class="btn btn-info" id="showsurveyBTN" disabled="disabled" onclick="showSurveyBUTTON();">Show Survey Responses
                </button>
            </div>
        </div>


    </div>
</div>
</div>
</form>
<?php
staff_footer();
?>
