<?php
// Copyright (c) 2019-2022 Leane Verhulst. All rights reserved. See copyright document for more details.

function renderMyDetails ($title, $error, $message, $dayjob, $accessibilityissues, $ethnicity, $gender, $sexualorientation, $agerangeid, $pronounid, $pronounother) {

    participant_header($title, false, 'Normal', true);

    echo("<div class=\"container mt-2\">");

    if ($error) {
        echo "<p class=\"alert alert-error\">Database not updated." . $message . "</p>";
    } elseif ($message != "") {
        echo "<p class=\"alert alert-success\">" . $message . "</p>";
    }
    if (!may_I('my_gen_int_write')) {
        echo "<p class=\"alert alert-info\">Changes cannot be made at this time.</p>\n";
    }

    echo "<div class=\"card\">";
    echo "<div class=\"card-header\">";
    echo "<h5>" . CON_NAME . " Optional Demographic Details</h5>\n";
    echo "</div>";
    echo "<div class=\"card-body\">";
    echo CON_NAME;
    echo " is committed to diverse panelist representation on our program items. To help us do that, please consider filling in the following OPTIONAL items of demographic information. All answers will be kept strictly confidential.";
    echo "</div>";
    echo "</div>";

    echo "<form name=\"addform\" method=\"POST\" action=\"SubmitMyDetails.php\">\n";

    echo "<div class=\"card\">";
    echo "<div class=\"card-body\">";

    echo "        <div class=\"row\">\n";  //first row

    echo "            <div class=\"col-auto\">\n";
    echo "                <label for=\"dayjob\">Day Job: </label>\n";
    echo "            </div>\n";
    echo "            <div class=\"col-auto\">\n";
    echo "                <input type=\"text\" size=\"20\" class=\"form-control\" name=\"dayjob\" value=\"" . htmlspecialchars($dayjob, ENT_COMPAT) . "\"";
    if (!may_I('my_gen_int_write')) {
        echo " readonly class=\"readonly\"";
    }
    echo ">\n";
    echo "            </div>\n";

    echo "            <div class=\"col-auto\">\n";
    echo "                <label for=\"agerangeid\">Age Range: </label>\n";
    echo "            </div>\n";
    echo "            <div class=\"col-auto\">\n";
    echo "                <select name=\"agerangeid\" class=\"form-control\">\n";
    populate_select_from_table("AgeRanges", $agerangeid, "", false);
    echo "                </select>\n";
    echo "            </div>\n";

    echo "            <div class=\"col-auto\">\n";
    echo "                <label for=\"ethnicity\">Race/Ethnicity: </label>\n";
    echo "            </div>\n";
    echo "            <div class=\"col-auto\">\n";
    echo "                <input type=\"text\" size=\"20\" class=\"form-control\" name=\"ethnicity\" value=\"" . htmlspecialchars($ethnicity, ENT_COMPAT) . "\"";
    if (!may_I('my_gen_int_write')) {
        echo " readonly class=\"readonly\"";
    }
    echo ">\n";
    echo "            </div>\n";

    echo "        </div>\n";   //end of top row



    echo "        <div class=\"row mt-3\">\n";    //second row

    echo "            <div class=\"col-12\">\n";
    echo "                <label for=\"accessibilityissues\">Do you have any accessibility issues that we should be aware of?</label>\n";
    echo "                <textarea class=\"form-control\" name=\"accessibilityissues\" rows=5";
    if (!may_I('my_gen_int_write')) {
        echo " readonly class=\"readonly\"";
    }
    echo ">" . htmlspecialchars($accessibilityissues, ENT_COMPAT) . "</textarea>\n";
    echo "            </div>\n";

    echo "        </div>\n";    //end of second row


    echo "        <div class=\"row mt-3\">\n";    //third row

    echo "            <div class=\"col-auto\">\n";
    echo "                <label for=\"gender\">Gender: </label>\n";
    echo "            </div>\n";
    echo "            <div class=\"col-auto\">\n";
    echo "                <input type=\"text\" size=\"20\" class=\"form-control\" name=\"gender\" value=\"" . htmlspecialchars($gender, ENT_COMPAT) . "\"";
    if (!may_I('my_gen_int_write')) {
        echo " readonly class=\"readonly\"";
    }
    echo ">\n";
    echo "            </div>\n";

    echo "            <div class=\"col-auto\">\n";
    echo "                <label for=\"sexualorientation\">Sexual Orientation: </label>\n";
    echo "            </div>\n";
    echo "            <div class=\"col-auto\">\n";
    echo "                <input type=\"text\" size=\"20\" class=\"form-control\" name=\"sexualorientation\" value=\"" . htmlspecialchars($sexualorientation, ENT_COMPAT) . "\"";
    if (!may_I('my_gen_int_write')) {
        echo " readonly class=\"readonly\"";
    }
    echo ">\n";
    echo "            </div>\n"; 

    echo "        </div>\n";   //end of third row


    echo "        <div class=\"row mt-3\">\n";    //fourth row

    echo "            <div class=\"col-auto\">\n";
    echo "                <label for=\"pronounid\">My pronouns are: </label>\n";
    echo "            </div>\n";
    echo "            <div class=\"col-auto\">\n";
    echo "                <select name=\"pronounid\" class=\"form-control\">\n";
    populate_select_from_table("Pronouns", $pronounid, "", false);
    echo "                </select>\n";
    echo "            </div>\n";

    echo "        </div>\n";    //end of fourth row


    echo "        <div class=\"row mt-3\">\n";    //fifth row

    echo "            <div class=\"col-auto\">\n";
    echo "                <label for=\"pronounother\">If you selected \"other\" for your pronouns, provide your pronouns here: </label>\n";
    echo "            </div>\n";
    echo "            <div class=\"col-auto\">\n";
    echo "                <input type=\"text\" size=\"20\" class=\"form-control\" name=\"pronounother\" value=\"" . htmlspecialchars($pronounother, ENT_COMPAT) . "\"";
    if (!may_I('my_gen_int_write')) {
        echo " readonly class=\"readonly\"";
    }
    echo ">\n";
    echo "            </div>\n";

    echo "        </div>\n";    //end of fifth row


    if (may_I('my_gen_int_write')) {
        echo "<div id=\"submit\"><button class=\"SubmitButton btn btn-primary\" type=\"submit\" name=\"submit\">Save</button></div>\n";
    }



    echo "</div>";   // end of card body
    echo "</div>";   // end of card


    echo "</form>";   // end of form



    echo "</div>";  //end of container


    participant_footer();

} ?>
