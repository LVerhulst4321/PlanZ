<?php

/**
 * Class for checking for site problems.
 */
class SiteHealth
{
    private const UPGRADE_PATH = '/../Install/Upgrade_dbase';
    private const REPORTS_BS4_INCLUDE = 'ReportMenuBS4Include.php';
    private const REPORTS_INCLUDE = 'ReportMenuInclude.php';
    private const STAFF_REPORTS_INCLUDE = 'staffReportsInCategoryInclude.php';
    private const REPORTS_PATH = './reports';
    private const REACT_DIST_DIR = '/dist';
    private const REACT_DIST_FILE = 'planzReactApp.js';
    private const REACT_CLIENT_PATH = '/../client/src/';

    protected array $problems = [];

    /**
     * Check if patch file is loged in PatchLog table.
     *
     * @param string $fileName - The file to check if already run.
     *
     * @return bool
     */
    protected function checkPatchApplied(string $fileName): bool
    {
        $query = <<<EOF
            SELECT count(1) AS `ct`
                FROM PatchLog
                WHERE patchname='$fileName';
        EOF;
        if (!$result = mysqli_query_exit_on_error($query)) {
            exit(); // Should have exited already
        }
        [$count] = mysqli_fetch_array($result, MYSQLI_NUM);
        mysqli_free_result($result);
        return ($count == 1);
    }

    /**
     * Check for files in the Upgrade_dbase directory, and verify against
     * PatchLog.
     *
     * @return void
     */
    protected function findAndCheckPatches(): void
    {
        $directory = dir(dirname(__FILE__) . self::UPGRADE_PATH);
        while (false !== ($file = $directory->read())) {
            if (preg_match('/\.sql$/', $file)) {
                if (!$this->checkPatchApplied($file)) {
                    // Patch file has not been applied.
                    // Record problem and exit, as no need to check further.
                    $this->problems[] =
                        "Database patches need to be applied. " .
                        "Please see Install/INSTALL.md for details.";
                    return;
                }
            }
        }
    }

    /**
     * Check the report directory files exist, and that they are newer than the
     * newest report files.
     *
     * @return void
     */
    protected function checkReportMenus(): void
    {
        // First make sure the three include files exist.
        if (!(
            file_exists(REPORT_INCLUDE_DIRECTORY . self::REPORTS_BS4_INCLUDE) &&
            file_exists(REPORT_INCLUDE_DIRECTORY . self::REPORTS_INCLUDE) &&
            file_exists(REPORT_INCLUDE_DIRECTORY . self::STAFF_REPORTS_INCLUDE)
        )) {
            $this->problems[] =
                "Report menus have not been built. " .
                "Ask your admin to build them.";
        }

        // Get the oldest creation date of the include files.
        $min_date = min(
            filemtime(REPORT_INCLUDE_DIRECTORY . self::REPORTS_BS4_INCLUDE),
            filemtime(REPORT_INCLUDE_DIRECTORY . self::REPORTS_INCLUDE),
            filemtime(REPORT_INCLUDE_DIRECTORY . self::STAFF_REPORTS_INCLUDE),
        );

        // Check files in reports directory against menu creation date.
        $path = dirname(__FILE__) . '/' . self::REPORTS_PATH;
        $directory = dir($path);
        while (false !== ($file = $directory->read())) {
            if (preg_match('/\.php$/', $file)) {
                $report_date = filemtime($path . '/' . $file);
                if ($report_date > $min_date) {
                    $this->problems[] =
                        "Report menus were last build on date " .
                        date("Y-m-d", $min_date) .
                        " but report found with date of " .
                        date("Y-m-d", $report_date) .
                        ". Ask your admin to rebuild the menus.";
                    // Once we know rebuild needed, no need to check further.
                    return;
                }
            }
        }

    }

    protected function findNewestFile(string $path, string $ext): int
    {
        $max_date = 0;
        $directory = dir($path);
        while (false !== ($file = $directory->read())) {
            if ($file == '.' || $file == '..') {
                continue;
            }
            $file_path = $path . '/' . $file;
            // If current file is a direcory, get date of latest file in it.
            if (is_dir($file_path)) {
                $sub_max_date = $this->findNewestFile($file_path, $ext);
                if ($sub_max_date > $max_date) {
                    $max_date = $sub_max_date;
                }
            }
            // If file has correct extension, check if it's newer than current.
            if (preg_match("/\.$ext\$/", $file)) {
                $file_date = filemtime($file_path);
                if ($file_date > $max_date) {
                    $max_date = $file_date;
                }
            }
        }
        return $max_date;
    }

    protected function checkReactClient(): void
    {
        // Check React distribution file exists.
        $base_dir = dirname(__FILE__);
        $dist_dir = $base_dir . self::REACT_DIST_DIR;
        $dist_file = $dist_dir . '/' . self::REACT_DIST_FILE;
        if (!(file_exists($dist_dir) && file_exists($dist_file))) {
            $this->problems[] =
                "React client not found in " .
                $dist_file .
                ". Application will not perform correctly without it. " .
                "Please ask your site admin to check documentation " .
                "client/README.md.";
            return;
        }
        // Get date of distribution file.
        $dist_date = filemtime($dist_file);
        // Find the modify date of the newest source file.
        $src_path = $base_dir . self::REACT_CLIENT_PATH;
        $newest_src_date = $this->findNewestFile($src_path, 'jsx');
        if ($newest_src_date > $dist_date) {
            $this->problems[] =
                "React client built on " .
                date('Y-m-d', $dist_date) .
                " but source files modified on " .
                date('Y-m-d', $newest_src_date) .
                ". Please inform site admin that a rebuild is needed. " .
                "See documentation at client/README.md.";
        }
    }

    /**
     * Check the site site for problems.
     *
     * @return void
     */
    public function findSiteProblems(): void
    {
        $this->findAndCheckPatches();
        $this->checkReportMenus();
        $this->checkReactClient();
    }

    /**
     * Return a rendered string listing problems with the site.
     *
     * @return string
     */
    public function renderSiteStatus(): string
    {
        if (count($this->problems) == 0) {
            return "<p>Site is healthy.</p>";
        }

        $text = "<p>This site has the following problems:</p>";
        $text .= "<ul>";
        foreach ($this->problems as $problem) {
            $text .= "<li>$problem</li>";
        }
        $text .= "</ul>";
        $text .= "<p>Please contact your administrator to resolve.";

        return $text;
    }
}
