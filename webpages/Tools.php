<?php
    global $linki, $title;
    $title = "Tools and Utilities";
    require_once(__DIR__ . '/StaffCommonCode.php');
    require_once(__DIR__ . '/tool_model.php');
    require_once(__DIR__ . '/api/admin/module_model.php');

    class ToolHelper {

        public static function getAllTools($db) {

            $result = array(new Tool("Table Tents", "Produce a printable version of the table tents for the various con sessions.", "TableTentsConfig.php"));

            $modules = PlanzModule::findAllEnabledModules($db);

            foreach ($modules as $module) {
                $fileName = $module->getDescriptorFileName();
                try {
                    if (file_exists(__DIR__ . $fileName)) {
                        require_once(__DIR__ . $fileName);

                        $moduleDescriptor = $module->getDescriptorClass();

                        if ($moduleDescriptor) {
                            $method = $moduleDescriptor->getMethod("getTools");
                            if ($method->getModifiers() & ReflectionMethod::IS_STATIC) {
                                $tools = $method->invoke(null);
                                $result = array_merge($result, $tools);
                            }
                        }
                    }
                } catch (Exception|Error $e) {
                    // skip it
                }
            }
            return $result;
        }
    }

    staff_header($title, true);

?>

</div>
<div class="container">

    <div class="list-group mt-3 mb-2">
<?php
    $tools = ToolHelper::getAllTools($linki);
    foreach ($tools as $tool) {
?>
        <div class="list-group-item flex-column align-items-start">
            <div class="row">
                <div class="col-md-10">
                    <h4><?php echo $tool->name ?></h4>
                    <div><?php echo $tool->description ?></div>
                </div>
                <div class="col-md-2 text-right">
                    <a href="<?php echo $tool->href ?>" class="btn btn-primary w-75">Select</a>
                </div>
            </div>
        </div>

<?php
    }
?>
    </div>

</div>
<div class="container-fluid">



<?php staff_footer(); ?>