<?php
// Copyright (c) 2005-2021 Peter Olszowka. All rights reserved. See copyright document for more details.

function renderMyInterests($title, $error, $message, $rolearray, $interestarray) {
    global $link, $yespanels, $nopanels, $yespeople, $nopeople;
    global $otherroles, $newrow;
    $rolerows = $rolearray['count'];
    $interestrows = $interestarray['count'];

    participant_header($title, false, 'Normal', true);
    echo("<div class=\"mt-2\">");
    if ($error) {
        echo "<p class=\"alert alert-error\">Database not updated.<br>" . $message . "</p>";
    } elseif ($message != "") {
        echo "<p class=\"alert alert-success\">" . $message . "</p>";
    }
    if (!may_I('my_gen_int_write')) {
        echo "<p>We're sorry, but we are unable to accept your suggestions at this time.\n";
    }
    echo "<form name=\"addform\" method=\"POST\" action=\"SubmitMyInterests.php\" >\n";
    echo "<input type=\"hidden\" name=\"newrow\" value=\"" . ($newrow ? 1 : 0) . "\" />\n";
    echo "<input type=\"hidden\" name=\"rolerows\" value=\"" . $rolerows . "\" />\n";
    echo "<input type=\"hidden\" name=\"interestrows\" value=\"" . $interestrows . "\" />\n";
    echo "<div class=\"row mt-3\">\n";
    echo "  <div class=\"col-md-6\">\n";
    echo "    <label for=\"yespanels\">Workshops or presentations I'd like to run:</label>\n";
    echo "    <textarea class=\"form-control\" id=\"yespanels\" name=\"yespanels\" rows=\"5\" cols=\"72\"";
    if (!may_I('my_gen_int_write')) {
        echo " readonly class=\"readonly\"";
    }
    echo ">" . htmlspecialchars($yespanels, ENT_COMPAT) . "</textarea>\n";

    echo "  </div>\n";
    echo "  <div class=\"col-md-6\">\n";
    echo "    <label for=\"nopanels\">Panel types I am not interested in participating in:</label>\n";
    echo "    <textarea class=\"form-control\" id=\"nopanels\" name=\"nopanels\" rows=\"5\" cols=\"72\"";
    if (!may_I('my_gen_int_write')) {
        echo " readonly class=\"readonly\"";
    }
    echo ">" . htmlspecialchars($nopanels, ENT_COMPAT) . "</textarea>\n";
    echo "    </div>\n";
    echo "</div>\n";
    echo "<div class=\"row mt-3\">\n";
    echo "  <div class=\"col-md-6\">\n";
    echo "    <label for=\"yespeople\">People with whom I'd like to be on a session: (Leave blank for none)</label>\n";
    echo "    <textarea class=\"form-control\" id=\"yespeople\" name=\"yespeople\" rows=\"5\" cols=\"72\"";
    if (!may_I('my_gen_int_write')) {
        echo " readonly class=\"readonly\"";
    }
    echo ">" . htmlspecialchars($yespeople, ENT_COMPAT) . "</textarea>\n";
    echo "  </div>\n";
    echo "  <div class=\"col-md-6\">\n";
    echo "    <label for=\"nopeople\">People with whom I'd rather not be on a session: (Leave blank for none)</label>\n";
    echo "    <textarea class=\"form-control\" id=\"nopeople\" name=\"nopeople\" rows=\"5\" cols=\"72\"";
    if (!may_I('my_gen_int_write')) {
        echo " readonly class=\"readonly\"";
    }
    echo ">" . htmlspecialchars($nopeople, ENT_COMPAT) . "</textarea>\n";
    echo "  </div>\n";
    echo "</div>\n";


    echo "<p class=\"mt-3\">Roles I'm willing to take on:</p>\n";
    echo "<div class=\"row mt-3\">\n";
    echo "    <div class=\"col-12 roles-list-container\">\n";
    for ($i = 1; $i < $rolerows; $i++) {
        echo "        <div class=\"role-entry-container\">\n";
        echo "            <label for=\"willdorole" . $i . "\">\n";
        echo "                <input type=\"checkbox\" name=\"willdorole" . $i . "\" id=\"willdorole" . $i . "\"";
        if (isset($rolearray[$i]["badgeid"])) {
            echo " checked";
        }
        if (!may_I('my_gen_int_write')) {
            echo " disabled";
        }
        echo " class=\"mr-2\" />";
        echo $rolearray[$i]["rolename"] . "</label>\n";
        echo "            <input type=\"hidden\" name=\"diddorole" . $i . "\" value=\"";
        echo ((isset($rolearray[$i]["badgeid"])) ? 1 : 0) . "\" />\n";
        echo "            <input type=\"hidden\" name=\"roleid" . $i . "\" value=\"" . $rolearray[$i]["roleid"] . "\" />\n";
        echo "            <input type=\"hidden\" name=\"rolename" . $i . "\" value=\"" . $rolearray[$i]["rolename"] . "\" />\n";
        echo "        </div>\n";
    }

    echo "        <div class=\"role-entry-container\">\n";
    echo "            <label for=\"willdorole0\">\n";
    echo "                <input type=\"checkbox\" name=\"willdorole0\" id=\"willdorole0\" ";
    if (isset($rolearray[0]["badgeid"])) {
        echo "checked";
    }
    if (!may_I('my_gen_int_write')) {
        echo " disabled";
    }
    echo " class=\"mr-2\"/>";
    echo $rolearray[0]["rolename"] . "  (Please describe below)</label>\n";
    echo "            <input type=hidden name=\"roleid0\" value=\"" . $rolearray[0]["roleid"] . "\" />\n";
    echo "            <input type=hidden name=\"rolename0\" value=\"" . $rolearray[0]["rolename"] . "\" />\n";
    echo "            <input type=hidden name=\"diddorole0\" value=\"";
    echo ((isset($rolearray[0]["badgeid"])) ? 1 : 0) . "\" />\n";
    echo "</div></div></div>\n";
    echo "<div class=\"row\"><div class=\"col-12\">\n";
    echo "<label class=\"mt-3\" for=\"otherroles\">Description for \"Other\" Roles:</label>\n";
    echo "<textarea class=\"form-control\" id=\"otherroles\" name=\"otherroles\" rows=\"5\" cols=\"72\"";
    if (!may_I('my_gen_int_write')) {
        echo " readonly class=\"readonly\"";
    }
    echo ">" . htmlspecialchars($otherroles, ENT_COMPAT) . "</textarea>\n";
    echo "</div></div>\n";


    echo "<p class=\"mt-3\">From which of the following areas would you consider being a panelist? (Check all that apply):</p>\n";
    echo "<div class=\"row mt-3\">\n";
    echo "    <div class=\"col-12 interests-list-container\">\n";
    for ($i = 1; $i < $interestrows; $i++) {
        echo "        <div class=\"interest-entry-container\">\n";
        echo "            <label for=\"willdointerest" . $i . "\">\n";
        echo "                <input type=\"checkbox\" name=\"willdointerest" . $i . "\" id=\"willdointerest" . $i . "\"";
        if (isset($interestarray[$i]["badgeid"])) {
            echo " checked";
        }
        if (!may_I('my_gen_int_write')) {
            echo " disabled";
        }
        echo " class=\"mr-2\" />";
        echo $interestarray[$i]["interestname"] . "</label>\n";
        echo "            <input type=\"hidden\" name=\"diddointerest" . $i . "\" value=\"";
        echo ((isset($interestarray[$i]["badgeid"])) ? 1 : 0) . "\" />\n";
        echo "            <input type=\"hidden\" name=\"interestid" . $i . "\" value=\"" . $interestarray[$i]["interestid"] . "\" />\n";
        echo "            <input type=\"hidden\" name=\"interestname" . $i . "\" value=\"" . $interestarray[$i]["interestname"] . "\" />\n";
        echo "        </div>\n";
    }
    echo "</div></div>\n";


    echo "<div id=\"submit\" class=\"row mt-3\"><div class=\"col-12\">\n";
    if (may_I('my_gen_int_write')) {
        echo "<button class=\"btn btn-primary\" type=\"submit\" name=\"submit\" >Save</button>\n";
    }
    echo "</div></div>\n";
    echo "</form>\n";
    echo "</div>\n";
} ?>
