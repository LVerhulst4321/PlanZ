<?php

global $title;

error_reporting(E_ALL); ini_set('display_errors', true);

//require_once('db_functions.php');
//require_once('error_functions.php');
require_once('CommonCode.php');
require_once('GridHtmlHeader.php');
require_once('grid_functions.php');
require_once('GridHeader.php');
require_once('GridFooter.php');

$title="Public - Display Programming Grid";

$ConStartDatim=CON_START_DATIM; // make it a variable so it can be substituted


$query = "SELECT current FROM Phases WHERE phaseid=10";  //10=Show public reports. See if it's on.
if (!$result = mysqli_query_exit_on_error($query)) {
    exit(); // Should have exited already
}
$row = mysqli_fetch_assoc($result);
mysqli_free_result($result);
$showPublicReports = $row["current"];

if ($showPublicReports) {
    //build_location_arrays returns $locations, $locationscss;
    //need to add logic to allow user to pick a room report group
    $roomstodisplaylist = 'all';
    //$roomstodisplaylist = '2';
    if (!build_location_arrays($linki, $roomstodisplaylist)) {
        $message_error = "Failed to retrieve location information. " . $message_error;
        RenderError($message_error);
        exit();
    }
    //build additional css for all of the locations
    $cssoutput = output_grid_css($locationscss);

    //call the page header and pass along the additional css so that it will be included on the page header
    grid_header($title, $cssoutput);

    //build a public grid of locations for every 15 minutes (900 seconds)
    $locationGrid = build_location_grid($linki, $locations, 'public', 900);
    $content = output_locationgrid($linki, $locationGrid, $locations, 'public');

} else {
    grid_header($title);
}

?>

<body>
<div class="container-fluid">


            <div class="container-fluid">
                <!-- Header -->
                <header class="header-wrapper" id="top">
                    <div id="reg-header-container" class="collapsible-wrapper">
                        <div id="reg-header">
                            <div class="header-contents">
                                <img src="<?php echo BASE_PATH; ?>images/Plan-Z-Logo-250.png" alt="Convention logo" class="d-none d-lg-block" />
                                <h1 class="d-none d-md-block"><span class="d-none d-lg-inline"> <?php echo CON_NAME; ?> Grid of Scheduled Events</span></h1>
                            </div>
                        </div>
                    </div>
                </header>
            </div>

<?php
    //Use the tags below to debug the arrays.
    //echo "<pre>";
    //print_r(implode(",", $locations));
    //print_r($locations);
    //print_r($locationscss);
    //print_r($locationGrid);
    //echo "</pre>";
?>


<?php
//Check if Phase is turned on to allow the report to be displayed
if ($showPublicReports) {
?>

    <div class="container-fluid">
    <div class="container">

    <p>(NOTE: All events are subject to last minute changes.  Please check back often.)<br />
    Generated on: <?php echo date('l jS \of F Y h:i:s A'); ?><br />
    Hover over a panel to get a description of the item.</p>
    <br />
    <h2 class="text-center">All times are Central timezone.</h2>

    <br />


<!-- Need to add code to look at the number of convention days for this section. -->

    <div class="row">
    <div class="col">
    <a href="#grid-Thursday">Thursday</a>
    </div>
    <div class="col">
    <a href="#grid-Friday">Friday</a>
    </div>
    <div class="col">
    <a href="#grid-Saturday">Saturday</a>
    </div>
    <div class="col">
    <a href="#grid-Sunday">Sunday</a>
    </div>
    </div>

    <br />
    <br />

    </div>      <!-- end of container -->
    </div>      <!-- end of container-fluid -->


    <?=$content?>

    <br />
    <br />
    <br />

<?php
} else {
?>

    <div class="container-fluid">
    <div class="container">
    <p>The schedule for the convention is not yet available. Please check back later.<br /></p>
    </div>      <!-- end of container -->
    </div>      <!-- end of container-fluid -->

<?php
}
?>


<?php grid_footer(); ?>
