<?php

    $title="Staff - Create KonOpas File";
    require_once('db_functions.php');
    require_once('error_functions.php');
    require_once('render_functions.php');
    require_once('StaffCommonCode.php');
    require_once('konOpas_func.php');

    if (!empty($_GET['showpubstatus'])) {
        $showpubstatus = $_GET["showpubstatus"];
    }
    else {
        $showpubstatus = 2;
    }

    if (!empty($_GET['showbio'])) {
        $showbio = $_GET["showbio"];
    }
    else {
        $showbio = 1;
    }

    staff_header($title, true);

?>

<div class="container">
    <div class="card">
        <div class="card-body">
            <p> This tool creates the KonOpas database files to update the KonOpas schedule.</p>
            <p class="mb-5"> It also creates the ConClár database files to update the ConClár schedule.</p>

<?php


    $results = retrieveKonOpasData($showpubstatus, $showbio);
    $infofile = retrieveInfoData();

    if ($results["message_error"]) {
        error_log("StaffCreateKonOpas.php: " . $results["message_error"]);
        RenderError($results["message_error"]);
        exit();
    }
    if ($results["konopas"]) {
        $resultsFile = fopen(JSON_EXTRACT_DIRECTORY . "program.js","w");
        if ($resultsFile === FALSE) {
            $message_error = "StaffCreateKonOpas.php: Cannot open ../program.js for writing.";
            error_log($message_error);
            RenderError($message_error);
            exit(1);
        }
        if (fwrite($resultsFile, $results["program"]) === FALSE) {
            $message_error = "StaffCreateKonOpas.php: Error writing to ../program.js.";
            error_log($message_error);
            RenderError($message_error);
            fclose($resultsFile);
            exit(1);
        }
        fclose($resultsFile);
        echo('<p>The program data file was created.' . "\n");
        echo('<p>Number of program items: ' . $results["program_num_rows"] . "\n");

        $resultsFile = fopen(JSON_EXTRACT_DIRECTORY . "people.js","w");
        if ($resultsFile === FALSE) {
            $message_error = "StaffCreateKonOpas.php: Cannot open ../people.js for writing.";
            error_log($message_error);
            RenderError($message_error);
            exit(1);
        }
        if (fwrite($resultsFile, $results["people"]) === FALSE) {
            $message_error = "StaffCreateKonOpas.php: Error writing to ../people.js.";
            error_log($message_error);
            RenderError($message_error);
            fclose($resultsFile);
            exit(1);
        }
        fclose($resultsFile);
        echo('<p>The people data file was created.' . "\n");
        echo('<p>Number of participants: ' . $results["people_num_rows"] . "\n");

        $resultsFile = fopen(JSON_EXTRACT_DIRECTORY . "konopas.appcache","w");
        if ($resultsFile === FALSE) {
            $message_error = "StaffCreateKonOpas.php: Cannot open ../konopas.appcache for writing.";
            error_log($message_error);
            RenderError($message_error);
            exit(1);
        }
        if (fwrite($resultsFile, $results["konopas"]) === FALSE) {
            $message_error = "StaffCreateKonOpas.php: Error writing to ../konopas.appcache.";
            error_log($message_error);
            RenderError($message_error);
            fclose($resultsFile);
            exit(1);
        }
        fclose($resultsFile);
        echo('<p>The konopas.appcache data file was created.' . "\n");
    }

    //Create data file for online guide ConClár
    //Should make this a flag or something to control whether to make the file.
    if ($results["json"]) {
        $fileNameProd = JSON_EXTRACT_DIRECTORY . "konOpasData.json";
        $fileNameTest = JSON_EXTRACT_DIRECTORY . "konOpasDataTest.json";
        $resultsFileProd = fopen($fileNameProd,"wb");
        $resultsFileTest = fopen($fileNameTest,"wb");
        if ($resultsFileProd === FALSE) {
            $message_error = "StaffCreateKonOpas.php: Cannot open " . $fileNameProd . " for writing.";
            error_log($message_error);
            RenderError($message_error);
            exit(1);
        }
        if ($resultsFileTest === FALSE) {
            $message_error = "StaffCreateKonOpas.php: Cannot open " . $fileNameTest . " for writing.";
            error_log($message_error);
            RenderError($message_error);
            exit(1);
        }
        if (fwrite($resultsFileProd, $results["json"]) === FALSE) {
            $message_error = "StaffCreateKonOpas.php: Error writing to " . $fileNameProd . ".";
            error_log($message_error);
            RenderError($message_error);
            fclose($resultsFileProd);
            exit(1);
        }
        if (fwrite($resultsFileTest, $results["json"]) === FALSE) {
            $message_error = "StaffCreateKonOpas.php: Error writing to " . $fileNameTest . ".";
            error_log($message_error);
            RenderError($message_error);
            fclose($resultsFileTest);
            exit(1);
        }
        fclose($resultsFileProd);
        fclose($resultsFileTest);
        echo('<p>The ConClár data file was created.' . "\n");

    }
    if (empty($results)) {
        $message_error = "StaffCreateKonOpas.php: retrieveKonOpasData() did not return expected result or error indicator.";
        error_log($message_error);
        RenderError($message_error);
        exit();
    }

    //Create info file for online guide ConClár
    if ($infofile["message_error"]) {
        error_log("StaffCreateKonOpas.php: " . $infofile["message_error"]);
        RenderError($infofile["message_error"]);
        exit();
    }
    if ($infofile["output"]) {
        $fileNameProd = JSON_EXTRACT_DIRECTORY . "info.md";
        $fileNameTest = JSON_EXTRACT_DIRECTORY . "info_test.md";
        $infofileFileProd = fopen($fileNameProd,"w");
        $infofileFileTest = fopen($fileNameTest,"w");
        if ($infofileFileProd === FALSE) {
            $message_error = "StaffCreateKonOpas.php: Cannot open " . $fileNameProd . " for writing.";
            error_log($message_error);
            RenderError($message_error);
            exit(1);
        }
        if ($infofileFileTest === FALSE) {
            $message_error = "StaffCreateKonOpas.php: Cannot open " . $fileNameTest . " for writing.";
            error_log($message_error);
            RenderError($message_error);
            exit(1);
        }
        if (fwrite($infofileFileProd, $infofile["output"]) === FALSE) {
            $message_error = "StaffCreateKonOpas.php: Error writing to " . $fileNameProd . ".";
            error_log($message_error);
            RenderError($message_error);
            fclose($infofileFileProd);
            exit(1);
        }
        if (fwrite($infofileFileTest, $infofile["output"]) === FALSE) {
            $message_error = "StaffCreateKonOpas.php: Error writing to " . $fileNameTest . ".";
            error_log($message_error);
            RenderError($message_error);
            fclose($infofileFileTest);
            exit(1);
        }
        fclose($infofileFileProd);
        fclose($infofileFileTest);
        echo('<p>The ConClár info file was created.' . "\n");
    }
    if (empty($infofile)) {
        $message_error = "StaffCreateKonOpas.php: retrieveInfoData() did not return expected result or error indicator.";
        error_log($message_error);
        RenderError($message_error);
        exit();
    }

?>

            <p class="mt-5"> It will take roughly 5-10 minutes for the data to appear in the KonOpas app.</p>
            <p> Data shows up right away in the ConClár app, but the user will need to do a refresh.</p>

        </div>
    </div>
</div>

<?php


staff_footer();

?>
