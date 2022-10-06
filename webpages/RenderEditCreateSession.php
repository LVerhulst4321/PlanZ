<?php
// Copyright (c) 2011-2021 Peter Olszowka. All rights reserved. See copyright document for more details.
    // This function will output the page with the form to add or create a session
    // Variables
    //     action: "create" or "edit"
    //     session: array with all data of record to edit or defaults for create
    //     message1: a string to display before the form
    //     message2: an urgent string to display before the form and after m1
function RenderEditCreateSession ($action, $session, $message1, $message2) {
    global $name, $email, $debug, $title;
    require_once("StaffHeader.php");
    require_once("StaffFooter.php");
    if ($action === "create") {
        $title = "Create New Session";
    } elseif ($action === "edit") {
        $title = "Edit Session";
    } else {
        exit();
    }
    staff_header($title, true, false, false, false, true);
?>
    <nav class="navbar navbar-light bg-secondary mb-2">
        <div>
            <button class="btn btn-outline-light text-center mr-2 session-assignments" type="button"><i class="bi bi-people-fill h3"></i><br />Participants</button>
            <button class="btn btn-outline-light text-center mr-2 session-history" type="button"><i class="bi bi-clock-history h3"></i><br />History</button>
        </div>
    </nav>
<?php
    // still inside function RenderAddCreateSession
    if (strlen($message1) > 0) {
        echo "<p id=\"message1\" class=\"alert\">".$message1."</p>\n";
    }
    if (strlen($message2) > 0) {
        echo "<p id=\"message2\" class=\"alert alert-danger\">".$message2."</p>\n";
        exit(); // If there is a message2, then there is a fatal error.
    }
    if (isset($debug)) {
        echo $debug."<br>\n";
    }
?>
    <form name="sessform" method="POST" action="SubmitEditCreateSession.php">
        <input type="hidden" name="name" value="<?php echo htmlspecialchars($name, ENT_COMPAT);?>" />
        <input type="hidden" name="email" value="<?php echo htmlspecialchars($email, ENT_COMPAT);?>" />
        <!-- The pubno field is no longer used on the form, but the code expects it.-->
        <input type="hidden" name="pubno" value="<?php echo htmlspecialchars($session["pubno"], ENT_COMPAT);?>" />
        <div class="text-right mt-3">
            <button class="btn btn-primary" type=submit value="save" onclick="mysubmit()">Save</button>
        </div>
        <div class="row">
            <div class="form-group col-md-1">
                <label for="sessionid">Session #: </label>
                <input id="sessionid" type="text" class="form-control" size=4 name="sessionid" disabled readonly value="<?php echo htmlspecialchars($session["sessionid"],ENT_COMPAT);?>" />
            </div>
            <div class="form-group col-md-2">
                <label for="divisionid">Division: </label>
                <select name="divisionid" class="form-control">
                    <?php populate_select_from_table("Divisions", $session["divisionid"], "SELECT", FALSE); ?>
                </select>
            </div>
            <div class="form-group col-md-4">
<?php
    if (TRACK_TAG_USAGE !== "TAG_ONLY") {
?>
                <label  for="track">Track: </label>
                <select name="track" class="form-control">
                    <?php populate_select_from_table("Tracks", $session["track"], "SELECT", FALSE); ?>
                </select>
<?php
    } else {
?>
                <input type="hidden" name="track" value="<?php echo DEFAULT_TAG_ONLY_TRACK?>"/>
<?php
    }
?>
            </div>
            <div class="form-group col-md-3">
                <label for="type">Type: </label>
                <select name="type" class="form-control">
                    <?php populate_select_from_table("Types", $session["type"], "SELECT", FALSE); ?>
                </select>
            </div>
            <div class="form-group col-md-2">
                <label for="pubstatusid">Pub. Status: </label>
                <select name="pubstatusid" class="form-control">
                    <?php populate_select_from_table("PubStatuses", $session["pubstatusid"], "SELECT", FALSE); ?>
                </select>
            </div>
        </div>
        <div class="row">
            <div class="form-group col-md-5 offset-md-1">
                <label for="title">Title:</label>
                <input type="text" class="form-control" size="50" maxlength="<?php echo TITLE_MAX_LENGTH; ?>" name="title" value="<?php echo htmlspecialchars($session["title"],ENT_COMPAT);?>" />&nbsp;&nbsp;
            </div>
            <div class="col-md-2 pt-3">
                <div class="form-check ">
                    <input class="form-check-input" type="checkbox" value="invguest" id="invguest" <?php if ($session["invguest"]) {echo " checked ";} ?> name="invguest" />
                    <label class="form-check-label" for="invguest"> Invited Guests Only</label>&nbsp;&nbsp;&nbsp;
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" value="signup" id="signup" <?php if ($session["signup"]) {echo " checked ";} ?> name="signup" />
                    <label class="form-check-label" for="signup"> Signup Required</label>
                </div>
            </div>
            <div class="form-group col-md-2">
                <label for="kids">Kids:</label>
                <select name="kids" class="form-control">
                    <?php populate_select_from_table("KidsCategories", $session["kids"], "SELECT", FALSE); ?>
                </select>
            </div>
        </div>
<?php
    if (BILINGUAL === TRUE) {
?>
    <div class="row">
        <div class="form-group col-md-5 offset-md-1">
            <label for="secondtitle"><?php echo SECOND_TITLE_CAPTION;?></label>
            <input type="text" size="50" maxlength="<?php echo TITLE_MAX_LENGTH; ?>" class="form-control" name="secondtitle" value="<?php echo htmlspecialchars($session["secondtitle"],ENT_COMPAT);?>" />
        </div>
        <div class="form-group col-md-5 offset-md-1">
            <label for="languagestatusid">Session Language: </label>
            <select class="form-control" name="languagestatusid">
                <?php populate_select_from_table("LanguageStatuses", $session["languagestatusid"], "SELECT", FALSE);?>
            </select>
        </div>
    </div>
<?php
    } else {
?>
        <input type="hidden" name="secondtitle" value="<?php echo htmlspecialchars($session["secondtitle"],ENT_COMPAT);?>" />
        <input type="hidden" name="languagestatusid" value="<?php echo htmlspecialchars($session["languagestatusid"],ENT_COMPAT);?>" />
<?php
    }
?>
        <div class="row">
            <div class="form-group col-md-1 offset-md-1">
                <label for="atten">Est. Atten.:</label>
                <input class="form-control" type="number" size="3" name="atten" value="<?php echo htmlspecialchars($session["atten"],ENT_COMPAT);?>" />
            </div>
            <div class="form-group col-md-1">
                <label for="duration">Duration:</label>
                <input class="form-control" type="text" size="5" name="duration" value="<?php echo htmlspecialchars($session["duration"],ENT_COMPAT);?>" />
            </div>
            <div class="form-group col-md-2">
                <label  for="roomset">Room Set: </label>
                <select class="form-control"  name="roomset">
                    <?php populate_select_from_table("RoomSets", $session["roomset"], "SELECT", FALSE); ?>
                </select>
            </div>
            <div class="form-group col-md-2">
                <label for="status">Status:</label>
                <select name="status" class="form-control">
                    <?php populate_select_from_table("SessionStatuses", $session["status"], "", FALSE); ?>
                </select>
            </div>
            <div class="form-group col-md-2">
                <label for="participantlabel">Participant Label:</label>
                <input class="form-control" type="text" size="3" name="participantlabel" value="<?php echo htmlspecialchars($session["participantlabel"],ENT_COMPAT);?>" />
            </div>
            <div class="form-group col-md-2 offset-md-1">
                <label for="hashtag">Hashtag:</label>
                <input type="text" class="form-control" size="20" name="hashtag" id="hashtag" value="<?php echo htmlspecialchars($session["hashtag"],ENT_COMPAT);?>" />
            </div>

        </div>
        <div class="row">
<?php
    if (HTML_SESSION === TRUE) {
?>
            <div class="form-group col-md-6">
                <label for="progguidhtml">Description:</label>
                <textarea class="form-control" rows="4" cols="70" name="progguidhtml"
                    id="progguidhtml"><?php echo $session["progguidhtml"]?></textarea>
            </div>
            <div class="form-group col-md-6">
                <label for="progguiddesc">Plain Text Description (updates on Save):</label>
                <textarea class="form-control" rows="4" cols="70" name="progguiddesc" id="progguiddesc"
                    readonly="readonly"><?php echo htmlspecialchars($session["progguiddesc"],ENT_NOQUOTES);?></textarea>
<?php
        if (BILINGUAL === TRUE) {
?>
                <label class="control-label vert-sep vert-sep-above" for="pocketprogtext">
                    <?php echo SECOND_DESCRIPTION_CAPTION;?>:
                </label>
                <textarea class="form-control" rows="4" cols="70" 
                    name="pocketprogtext"><?php echo htmlspecialchars($session["pocketprogtext"],ENT_NOQUOTES);?></textarea>
<?php
        } else {
        // The pocketprogtext field is no longer used on the form, but the code expects it.
?>
                <input type="hidden" name="pocketprogtext" value="<?php echo htmlspecialchars($session["pocketprogtext"],ENT_COMPAT);?>" />
<?php
        }
?>
                <label for="persppartinfo">Prospective Participant Info:</label>
                <textarea class="form-control"
                          rows="5" cols="70" name="persppartinfo"><?php echo htmlspecialchars($session["persppartinfo"],ENT_NOQUOTES);?></textarea>
            </div>
<?php
    } else {
?>
        <div class="form-group col-md-6">
                <label for="progguiddesc">Description:</label>
                <textarea class="form-control"
                    rows="5" cols="70" name="progguiddesc"><?php echo htmlspecialchars($session["progguiddesc"],ENT_NOQUOTES);?></textarea>
            </div>
<?php
        if (BILINGUAL === TRUE) {
?>
            <div class="form-group col-md-6">
                <label for="pocketprogtext"><?php echo SECOND_DESCRIPTION_CAPTION;?>: </label>
                <textarea class="form-control"
                    rows="4" cols="70" name="pocketprogtext"><?php echo htmlspecialchars($session["pocketprogtext"],ENT_NOQUOTES);?></textarea>
            </div>
<?php
        } else {
            // The pocketprogtext field is no longer used on the form, but the code expects it.
?>
        <input type="hidden" name="pocketprogtext" value="<?php echo htmlspecialchars($session["pocketprogtext"],ENT_COMPAT);?>" />
<?php
        }
?>
            <div class="form-group col-md-6">
                <label for="persppartinfo">Prospective Participant Info:</label>
                <textarea class="form-control"
                          rows="5" cols="70" name="persppartinfo"><?php echo htmlspecialchars($session["persppartinfo"],ENT_NOQUOTES);?></textarea>
            </div>
<?php
     }
?>
        </div>


        <div class="row">
            <div class="form-group col-lg-4"> <!-- Features Box; -->
                <div><label>Required Room Features:</label></div>
                <select class="form-control form-control-multiselect" id="features" name="featdest[]" size=6 multiple>
                    <?php populate_multiselect_from_table("Features", $session["featdest"]); ?>
                </select>
            </div> <!-- Features -->
            <div class="form-group col-lg-4" > <!-- Services Box; -->
                <div><label>Required Room Services:</label></div>
                <select class="form-control form-control-multiselect" id="services" name="servdest[]" size=6 multiple>
                    <?php populate_multiselect_from_table("Services", $session["servdest"]); ?>
                </select>
            </div> <!-- Services -->
<?php
    if (TRACK_TAG_USAGE !== "TRACK_ONLY") {
?>
            <div class="form-group col-lg-4"> 
                <div><label for="tagdest">Tags:</label></div>
                <select class="form-control form-control-multiselect" id="tagdest" name="tagdest[]" multiple>
                    <?php populate_multiselect_from_table("Tags", $session["tagdest"]); ?>
                </select>
            </div>
        </div>
<?php
    } else {
?>
            <input type="hidden" name="tagdest[]" value="" />
<?php
    }
?>
        <hr class="nospace" />
        <div class="row">
            <div class="form-group col-md-4">
                <label for="notesforpart">Notes for Participants:</label>
                <textarea class="form-control"
                    rows="3" cols="70" name="notesforpart" ><?php echo htmlspecialchars($session["notesforpart"],ENT_NOQUOTES);?></textarea>
            </div>
            <div class="form-group col-md-4">
                <label for="servnotes">Notes for Tech and Hotel:</label>
                <textarea class="form-control"
                    rows="3" cols="70" name="servnotes" ><?php echo htmlspecialchars($session["servnotes"],ENT_NOQUOTES);?></textarea>
            </div>
            <div class="form-group col-md-4">
                <label for="notesforprog">Notes for Programming Committee:</label>
                <textarea class="form-control"
                    rows="3" cols="70" name="notesforprog" ><?php echo htmlspecialchars($session["notesforprog"],ENT_NOQUOTES);?></textarea>
            </div>
        </div>
<?php
    if (MEETING_LINK === TRUE) {
?>
        <div class="row mt-3">
            <div class="form-group col-md-6">
                <label for="mlink">Meeting Link:</label>
                <input type="text" class="form-control" size="80" maxlength="510" name="mlink" id="mlink" value="<?php echo htmlspecialchars($session["mlink"], ENT_COMPAT);?>" />
            </div>
        </div>
<?php
    }
?>
<?php
    if (STREAMING_LINK === TRUE) {
?>
        <div class="row mt-3">
            <div class="form-group col-md-6">
                <label for="streamlink">Streaming link or ID:</label>
                <input type="text" class="form-control" size="80" maxlength="510" name="streamlink" id="streamlink" value="<?php echo htmlspecialchars($session["streamlink"], ENT_COMPAT);?>" />
            </div>
        </div>
<?php
    }
?>
<?php
    if (SIGNUP_LINK === TRUE) {
?>
        <div class="row mt-3">
            <div class="form-group col-md-6">
                <label for="signlink">Signup Link:</label>
                <input type="text" class="form-control" size="80" maxlength="510" name="signlink" id="signlink" value="<?php echo htmlspecialchars($session["signlink"], ENT_COMPAT);?>" />
            </div>
        </div>
<?php
    }
?>
        <div class="text-right mt-3">
            <button class="btn btn-primary" type=submit value="save" onclick="mysubmit()">Save</button>
        </div>
        <input type="hidden" name="action" value="<?php echo ($action === "create") ? "create" : "edit"; ?>" />
    </form>
        
    <!-- Modal -->
    <div class="modal fade" id="historyModal" tabindex="-1" role="dialog" aria-labelledby="historyLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="historyLabel">Session History</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>Here's how this session has changed/evolved over time:</p>
                    <div class="history-content">

                    </div>
                </div>
            </div>
        </div>
    </div>
<?php
    $sessionId = $session["sessionid"];
?>
    <!-- Modal -->
    <div class="modal fade" id="assignmentModal" tabindex="-1" role="dialog" aria-labelledby="assignmentLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="assignmentLabel">Assigned Participants</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>The following folks are assigned to this session:</p>
                    <div class="assignment-content">

                    </div>
                </div>
                <div class="modal-footer">
<?php
                    echo "<a class=\"btn btn-primary\" href=\"/StaffAssignParticipants.php?selsess=$sessionId\">Modify</a>";
?>
                </div>
            </div>
        </div>
    </div>

    <script type="text/javascript" src="external/bootstrap-multiselect-1.1.7/bootstrap-multiselect.min.js"></script>
    <script type="text/javascript" src="https://unpkg.com/dayjs@1.8.21/dayjs.min.js"></script>
    <script type="text/javascript">
        $(document).ready(function() {
            $('.form-control-multiselect').multiselect();
        });
    </script>
    <script type="text/javascript" src="js/planzExtension.js"></script>
    <script type="text/javascript" src="js/planzExtensionEditSession.js"></script>
<?php
    staff_footer();
}
?>
