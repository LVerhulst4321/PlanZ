<?php

/**
 * Class for checking for site problems.
 */
class SiteHealth
{
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
        $directory = dir(dirname(__FILE__) . '/../Install/Upgrade_dbase');
        while (false !== ($file = $directory->read())) {
            if (preg_match('/\.sql$/', $file)) {
                if (!$this->checkPatchApplied($file)) {
                    // Patch file has not been applied.
                    // Record problem and exit, as no need to check further.
                    $this->problems[] = "Database patches need to be applied."
                        . "Please see Install/INSTALL.md for details.";
                    return;
                }
            }
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
        $text .= "<p>Please contact your administrator to resolve.";

        return $text;
    }
}
