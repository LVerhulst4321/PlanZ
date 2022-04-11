<?php
    global $title;
    $title = "Volunteering";
    require_once('PartCommonCode.php');
    participant_header($title, false, 'Normal', true);

    if (may_I('Volunteering')) {
?>
    <div class="container">
        <?php echo fetchCustomText('alerts') ?>

        <div id="app"></div>
        <script src="dist/planzReactApp.js"></script>
<?php 
    } else {
?>
        <div class="alert alert-warning">Volunteer Sign-up is not currently active.</a>
<?php 
    } 
?>
    </div>
<?php
    participant_footer(); 
?>