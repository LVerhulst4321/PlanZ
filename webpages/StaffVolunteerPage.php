<?php
    global $title;
    $title = "Schedule Volunteer Shifts";
    require_once('StaffCommonCode.php');
    staff_header($title, true);
?>

<div id="app"></div>
<script src="dist/planzReactApp.js"></script>

<?php staff_footer(); ?>