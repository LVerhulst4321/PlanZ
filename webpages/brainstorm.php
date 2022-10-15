<?php
    global $title;
    $title = "Brainstorm";
    require_once('PartCommonCode.php');
    participant_header($title, false, 'Normal', true);

    if (may_I('BrainstormSubmit')) {
?>
    <div class="container">
        <?php echo fetchCustomText('alerts') ?>

        <div id="app"></div>
        <script src="dist/planzReactApp.js"></script>
<?php 
    } else {
?>
        <div class="alert alert-warning">Brainstorm is not currently active.</a>
<?php 
    } 
?>
    </div>
<?php
    participant_footer(); 
?>