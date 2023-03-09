<?php
global $title;
$title = "Table Tents";
require_once __DIR__ . '/../../StaffCommonCode.php';
staff_header($title, true);
?>

</div>
<div class="container">

<?php
$params = ["basepath" => BASE_PATH];
RenderXSLT('TableTentsConfig.xsl', $params);
?>

</div>
<div class="container-fluid">

<?php staff_footer(); ?>
