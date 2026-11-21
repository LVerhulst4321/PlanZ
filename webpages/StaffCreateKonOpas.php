<?php

    $title="Staff - Publish KonOpas and ConClár";
    require_once('db_functions.php');
    require_once('error_functions.php');
    require_once('render_functions.php');
    require_once('StaffCommonCode.php');
    require_once('konOpas_func.php');

    $extractDirectory = rtrim(JSON_EXTRACT_DIRECTORY, "/") . "/";
    $lastPublishedFile = $extractDirectory . "conclar-v2.json";

    $defaultShowpubstatus = "2";
    $defaultShowbio = "1";
    $showpubstatus = isset($_REQUEST["showpubstatus"]) && $_REQUEST["showpubstatus"] !== "" ? $_REQUEST["showpubstatus"] : $defaultShowpubstatus;
    $showbio = isset($_REQUEST["showbio"]) && $_REQUEST["showbio"] !== "" ? $_REQUEST["showbio"] : $defaultShowbio;

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

    function lookUpPublisherName($badgeid) {
        $query = <<<EOD
SELECT COALESCE(NULLIF(P.pubsname, ''), CONCAT(CD.firstname, ' ', CD.lastname)) AS name
FROM
              Participants P
    LEFT JOIN CongoDump CD USING (badgeid)
WHERE P.badgeid = ?;
EOD;
        $result = mysqli_query_with_prepare_and_error_handling($query, "s", array($badgeid));
        if ($result === false) {
            return null;
        }
        $row = mysqli_fetch_assoc($result);
        return $row ? $row["name"] : null;
    }

    function renderLastPublished($lastPublishedFile) {
        clearstatcache();
        if (!file_exists($lastPublishedFile)) {
            echo "<p>Never published.</p>\n";
            return;
        }

        $dateFormat = "j F Y, H:i T";
        $data = json_decode(file_get_contents($lastPublishedFile), true);
        $info = is_array($data) && isset($data["info"]) && is_array($data["info"]) ? $data["info"] : array();

        $publishedAt = false;
        if (!empty($info["publishedAt"]) && is_string($info["publishedAt"])) {
            try {
                $publishedAt = new DateTime($info["publishedAt"]);
                $publishedAt->setTimezone(new DateTimeZone(date_default_timezone_get()));
            } catch (Exception $e) {
                $publishedAt = false;
            }
        }
        if ($publishedAt === false) {
            echo '<p>Last published on ' . date($dateFormat, filemtime($lastPublishedFile)) . ".</p>\n";
            return;
        }

        $message = 'Last published on ' . htmlspecialchars($publishedAt->format($dateFormat));
        if (!empty($info["publishedBy"]) && is_scalar($info["publishedBy"])) {
            $publisherBadgeid = (string) $info["publishedBy"];
            $publisherName = lookUpPublisherName($publisherBadgeid);
            if (!empty($publisherName)) {
                $message .= ' by <strong>' . htmlspecialchars($publisherName) . '</strong> (' . htmlspecialchars($publisherBadgeid) . ')';
            } else {
                $message .= ' by ' . htmlspecialchars($publisherBadgeid);
            }
        }
        $settings = array();
        if (isset($info["includePubStatuses"]) && is_array($info["includePubStatuses"])) {
            $statuses = array_filter($info["includePubStatuses"], 'is_string');
            $settings[] = empty($statuses) ? 'no sessions' : htmlspecialchars(implode(', ', $statuses)) . ' sessions';
        }
        if (isset($info["includeBios"])) {
            $settings[] = $info["includeBios"] ? 'bios included' : 'bios excluded';
        }
        if (!empty($settings)) {
            $message .= ': ' . implode(', ', $settings);
        }
        echo '<p>' . $message . ".</p>\n";
    }

    function pubStatusIdList($showpubstatus) {
        $ids = array_unique(array_map('intval', explode(',', $showpubstatus)));
        sort($ids);
        return $ids;
    }

    function describePubStatuses($pubStatusNames) {
        return empty($pubStatusNames) ? 'none' : implode(', ', $pubStatusNames);
    }

    function describeBios($showbio) {
        return $showbio === "1" ? 'included' : 'excluded';
    }

    function renderWhatWillBePublished($showpubstatus, $showbio, $results, $defaultShowpubstatus, $defaultShowbio) {
        $pubStatusNames = $results["pub_status_names"];
        $unknownIds = array_diff(pubStatusIdList($showpubstatus), array_map('intval', array_keys($pubStatusNames)));

        echo '<h5 class="mt-4">What will be published</h5>' . "\n";
        $isDefault = pubStatusIdList($showpubstatus) === pubStatusIdList($defaultShowpubstatus) && $showbio === $defaultShowbio;
        if (!$isDefault) {
            $defaultNames = retrievePubStatusNames($defaultShowpubstatus);
            $defaultText = $defaultNames === false ? 'status ID ' . $defaultShowpubstatus : describePubStatuses($defaultNames);
            echo '<div class="alert alert-warning">These are not the default settings (' . htmlspecialchars($defaultText)
                . ' sessions, bios ' . describeBios($defaultShowbio)
                . '). If you followed a link to this page, check these are the settings you intended before publishing.</div>' . "\n";
        }
        echo "<ul>\n";
        echo '<li>Sessions with publication status: <strong>' . htmlspecialchars(describePubStatuses($pubStatusNames)) . '</strong>';
        if (!empty($unknownIds)) {
            echo ' (unknown status IDs ignored: ' . htmlspecialchars(implode(', ', $unknownIds)) . ')';
        }
        echo "</li>\n";
        echo '<li>Program items: <strong>' . (int) $results["program_num_rows"] . "</strong></li>\n";
        echo '<li>Participants: <strong>' . (int) $results["people_num_rows"] . '</strong>, bios <strong>' . describeBios($showbio) . "</strong></li>\n";
        echo "</ul>\n";
    }

    function retrieveKonOpasDataOrExit($showpubstatus, $showbio, $badgeid) {
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
        return $results;
    }

    if (!isValidPubStatusList($showpubstatus)) {
        RenderError("Invalid showpubstatus: expected a comma-separated list of publication status IDs.");
        exit();
    }
    if ($showbio !== "0" && $showbio !== "1") {
        RenderError("Invalid showbio: expected 0 or 1.");
        exit();
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $results = retrieveKonOpasDataOrExit($showpubstatus, $showbio, $badgeid);

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

        $query = http_build_query(array("showpubstatus" => $showpubstatus, "showbio" => $showbio, "published" => "1"));
        header("Location: StaffCreateKonOpas.php?" . $query, true, 303);
        exit();
    }

    // Built on every view so the counts shown before publishing come from the same code that writes the files.
    $results = retrieveKonOpasDataOrExit($showpubstatus, $showbio, $badgeid);

    staff_header($title, true);

?>

<div class="container">
    <div class="card">
        <div class="card-body">
<?php if (!empty($_GET["published"])) { ?>
            <div class="alert alert-success">The schedule was published.</div>
            <script>
                // Drop the flag from the address bar so a refresh doesn't show the confirmation again.
                {
                    const url = new URL(window.location.href);
                    url.searchParams.delete("published");
                    history.replaceState(null, "", url);
                }
            </script>
<?php } ?>
            <p> This tool publishes the schedule to the KonOpas and ConClár guides.</p>

<?php
    renderWhatWillBePublished($showpubstatus, $showbio, $results, $defaultShowpubstatus, $defaultShowbio);
    renderLastPublished($lastPublishedFile);
?>

            <form method="post" action="StaffCreateKonOpas.php" class="mb-4">
                <input type="hidden" name="showpubstatus" value="<?php echo htmlspecialchars($showpubstatus); ?>" />
                <input type="hidden" name="showbio" value="<?php echo htmlspecialchars($showbio); ?>" />
                <button type="submit" class="btn btn-primary">Publish</button>
            </form>

            <p> Published to <code><?php echo htmlspecialchars($extractDirectory); ?></code>.</p>
            <p> It will take roughly 5-10 minutes for the data to appear in the KonOpas app.</p>
            <p> Data shows up right away in the ConClár app, but the user will need to do a refresh.</p>

        </div>
    </div>
</div>

<?php


staff_footer();

?>
