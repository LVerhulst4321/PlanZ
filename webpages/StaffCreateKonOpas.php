<?php

    $title="Staff - Create KonOpas File";
    require_once('db_functions.php');
    require_once('error_functions.php');
    require_once('render_functions.php');
    require_once('StaffCommonCode.php');
    require_once('konOpas_func.php');

    $extractDirectory = rtrim(JSON_EXTRACT_DIRECTORY, "/") . "/";

    $showpubstatus = isset($_GET["showpubstatus"]) && $_GET["showpubstatus"] !== "" ? $_GET["showpubstatus"] : "2";
    $showbio = isset($_GET["showbio"]) && $_GET["showbio"] !== "" ? $_GET["showbio"] : "1";

    if (!isValidPubStatusList($showpubstatus)) {
        RenderError("Invalid showpubstatus: expected a comma-separated list of publication status IDs.");
        exit();
    }
    if ($showbio !== "0" && $showbio !== "1") {
        RenderError("Invalid showbio: expected 0 or 1.");
        exit();
    }

    // Written via a temporary file and rename so the guide never fetches a half-written file.
    // The temporary file must be in the same directory for rename() to be atomic.
    function writeFileAtomically($path, $contents) {
        $tempPath = $path . '.tmp.' . bin2hex(random_bytes(4));
        if (file_put_contents($tempPath, $contents) === false) {
            // A failed write can still leave a partial file in the public directory.
            @unlink($tempPath);
            return false;
        }
        if (!rename($tempPath, $path)) {
            unlink($tempPath);
            return false;
        }
        return true;
    }

    staff_header($title, true);

?>

<div class="container">
    <div class="card">
        <div class="card-body">
            <p> This tool creates the KonOpas database files to update the KonOpas schedule.</p>
            <p class="mb-5"> It also creates the ConClár database files to update the ConClár schedule.</p>

<?php

    $results = retrieveKonOpasData($showpubstatus, $showbio, $badgeid);
    if (empty($results)) {
        $message_error = "StaffCreateKonOpas.php: retrieveKonOpasData() did not return expected result or error indicator.";
        error_log($message_error);
        RenderError($message_error);
        exit();
    }
    if (!empty($results["message_error"])) {
        error_log("StaffCreateKonOpas.php: " . $results["message_error"]);
        RenderError($results["message_error"]);
        exit();
    }

    $infofile = retrieveInfoData();
    if (empty($infofile)) {
        $message_error = "StaffCreateKonOpas.php: retrieveInfoData() did not return expected result or error indicator.";
        error_log($message_error);
        RenderError($message_error);
        exit();
    }
    if (!empty($infofile["message_error"])) {
        error_log("StaffCreateKonOpas.php: " . $infofile["message_error"]);
        RenderError($infofile["message_error"]);
        exit();
    }

    $fileContents = array(
        "program.js" => $results["program"],
        "people.js" => $results["people"],
        "konopas.appcache" => $results["konopas"],
        "konOpasData.json" => $results["json"],
        "konOpasDataTest.json" => $results["json"],
        "conclar-v2.json" => $results["conclar_v2"],
        "info.md" => $infofile["output"],
        "info_test.md" => $infofile["output"],
    );
    foreach ($fileContents as $fileName => $contents) {
        if (!writeFileAtomically($extractDirectory . $fileName, $contents)) {
            $message_error = "StaffCreateKonOpas.php: Error writing to " . $extractDirectory . $fileName . ".";
            error_log($message_error);
            RenderError($message_error);
            exit(1);
        }
    }

    echo "<p>The data files were created in <code>" . htmlspecialchars($extractDirectory) . "</code>.</p>\n";
    echo "<p>Number of program items: " . $results["program_num_rows"] . "</p>\n";
    echo "<p>Number of participants: " . $results["people_num_rows"] . "</p>\n";

?>

            <p class="mt-5"> It will take roughly 5-10 minutes for the data to appear in the KonOpas app.</p>
            <p> Data shows up right away in the ConClár app, but the user will need to do a refresh.</p>

        </div>
    </div>
</div>

<?php


staff_footer();

?>
