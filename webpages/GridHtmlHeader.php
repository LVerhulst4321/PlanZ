<?php
//	Copyright (c) 2019-2021 Peter Olszowka. All rights reserved. See copyright document for more details.
function grid_html_header($title, $bootstrap4 = false, $isDataTables = false, $reportColumns = false, $reportAdditionalOptions = false, $additionalcss = false, $cssstyle = false) {
    global $fullPage;
    require_once "javascript_functions.php";
?>
<!DOCTYPE html>
<html lang="en" <?php if ($fullPage) echo "class =\"full-page\""; ?> >
<head>
    <meta charset="utf-8">
    <title>PlanZ &ndash; <?php echo $title; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="<?php echo BASE_PATH; ?>images/favicon.ico" rel="shortcut icon" type="image/x-icon" />
<?php if ($bootstrap4) { ?>
    <link rel="stylesheet" href="<?php echo BASE_PATH; ?>external/bootstrap4.5.0/bootstrap.min.css" type="text/css" >
<?php } else { ?>
    <link rel="stylesheet" href="<?php echo BASE_PATH; ?>external/jqueryui1.8.16/jquery-ui-1.8.16.custom.css" type="text/css">
    <link rel="stylesheet" href="<?php echo BASE_PATH; ?>external/bootstrap2.3.2/bootstrap.css" type="text/css" >
    <link rel="stylesheet" href="<?php echo BASE_PATH; ?>external/bootstrap2.3.2/bootstrap-responsive.css" type="text/css" >
<?php } ?>
    <link rel="stylesheet" href="<?php echo BASE_PATH; ?>external/choices9.0.0/choices.min.css" type="text/css" >
    <link rel="stylesheet" href="<?php echo BASE_PATH; ?>external/tabulator-4.9.3/css/tabulator.min.css" type="text/css" >
    <link rel="stylesheet" href="<?php echo BASE_PATH; ?>css/zambia_common.css" type="text/css" media="screen" />

<?php if ($additionalcss) { ?>
    <link rel="stylesheet" href="<?php echo BASE_PATH.$additionalcss ?>" type="text/css" media="screen" />
<?php } ?>

<?php if ($bootstrap4) { ?>
    <link rel="stylesheet" href="<?php echo BASE_PATH; ?>css/zambia_bs4.css" type="text/css" media="screen" />
<?php
    if (defined('CON_THEME') && CON_THEME !== "") {
?>
    <link rel="stylesheet" href="<?php echo BASE_PATH.CON_THEME ?>" type="text/css" media="screen" />
<?php } ?>
<?php } else { ?>
    <link rel="stylesheet" href="<?php echo BASE_PATH; ?>css/zambia.css" type="text/css" media="screen" />
<?php } ?>

    <link rel="stylesheet" href="<?php echo BASE_PATH; ?>css/staffMaintainSchedule.css" type="text/css" media="screen" />
<?php if ($isDataTables) {
    echo "    <link rel=\"stylesheet\" href=\"".BASE_PATH."external/dataTables1.10.16/dataTables.css\" type=\"text/css\" />\n";
    if ($reportColumns) {
        echo "<meta id=\"reportColumns\" data-report-columns=\"";
        echo htmlentities(json_encode($reportColumns));
        echo "\">";
    }
    if ($reportAdditionalOptions) {
        echo "<meta id=\"reportAdditionalOptions\" data-report-additional-options=\"";
        echo htmlentities(json_encode($reportAdditionalOptions));
        echo "\">";
    }
}
?>
<?php
    if ($cssstyle !== "") {
?>
        <style>
            <?php echo $cssstyle ?>
        </style>
<?php
    }
?>
    <link rel="shortcut icon" href="<?php echo BASE_PATH; ?>images/favicon.ico">
    <script type="text/javascript">
        var thisPage="<?php echo $title; ?>";
        var conStartDateTime = new Date("<?php echo CON_START_DATIM; ?>".replace(/-/g,"/"));
        var STANDARD_BLOCK_LENGTH = "<?php echo STANDARD_BLOCK_LENGTH; ?>";
    </script>
<?php
    $isRecaptcha = $title == 'Forgot Password';
    /* "external" means 3rd party library */
    load_external_javascript($isDataTables, $isRecaptcha, $bootstrap4);
    load_internal_javascript($title, $isDataTables);
?>
</head>
<?php } ?>
