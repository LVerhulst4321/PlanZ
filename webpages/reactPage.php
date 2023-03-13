<?php
    global $title;
    function reactPage($pageTitle, $permissionAtom) {
        $title = $pageTitle;
        require_once(__DIR__ . '/StaffCommonCode.php');
        staff_header($title, true);

        if (isLoggedIn() && may_I($permissionAtom)) {
    ?>
        <div class="container">
            <?php echo fetchCustomText('alerts') ?>

            <div id="app"></div>
            <script src="dist/planzReactApp.js"></script>
<?php
        } else {
?>
        <div class="alert alert-warning">You do not have access to this function.</a>
<?php
        }
?>
    </div>
<?php
        staff_footer();
    }
?>