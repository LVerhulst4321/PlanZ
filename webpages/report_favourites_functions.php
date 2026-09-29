<?php
// Returns an array whose keys are the report names the given user has favourited.
function get_report_favourites($badgeid) {
    $favourites = array();
    $query = "SELECT reportname FROM ReportFavourites WHERE badgeid = ?;";
    $result = mysqli_query_with_prepare_and_exit_on_error($query, "s", array($badgeid));
    while ($row = mysqli_fetch_assoc($result)) {
        $favourites[$row['reportname']] = true;
    }
    mysqli_free_result($result);
    return $favourites;
}

function set_report_favourite($badgeid, $reportName, $favourite) {
    if ($favourite) {
        $query = "INSERT IGNORE INTO ReportFavourites (badgeid, reportname) VALUES (?, ?);";
    } else {
        $query = "DELETE FROM ReportFavourites WHERE badgeid = ? AND reportname = ?;";
    }
    return mysql_cmd_with_prepare($query, "ss", array($badgeid, $reportName)) !== null;
}

function render_report_favourite_button($reportName, $isFavourite) {
    $iconClass = $isFavourite ? 'bi-star-fill' : 'bi-star';
    $label = $isFavourite ? 'Remove from favourites' : 'Add to favourites';
    $encodedReportName = htmlspecialchars($reportName, ENT_QUOTES);
    return "<button type=\"button\" class=\"btn btn-link report-favourite\" "
        . "data-report-name=\"$encodedReportName\" data-favourite=\"" . ($isFavourite ? 'true' : 'false') . "\" "
        . "title=\"$label\" aria-label=\"$label\" aria-pressed=\"" . ($isFavourite ? 'true' : 'false') . "\">"
        . "<i class=\"bi $iconClass\"></i></button>";
}

function render_report_favourite_scripts() {
    echo "<script type=\"text/javascript\" src=\"./js/planzExtension.js\"></script>\n";
    echo "<script type=\"text/javascript\" src=\"./js/ReportFavourites.js\"></script>\n";
}
?>
