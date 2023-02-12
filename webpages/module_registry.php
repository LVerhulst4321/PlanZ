<?php

class ModuleRegistry {

    public static function getModuleDescriptorFileName($modulePackageName) {
        return "/module/" . str_replace('.', '/', $modulePackageName) . "_module.php";
    }

    public static function getModuleDescriptorClassName($modulePackageName) {
        $index = strpos($modulePackageName, ".");
        if ($index > 0) {
            $namespaceName = substr($modulePackageName, 0, $index);
            if ($namespaceName == "planz") {
                $namespaceName = 'PlanZ\Module\\';
            } else {
                $namespaceName = ucfirst(mb_strtolower($namespaceName)) + '\\';
            }

            $temp = substr($modulePackageName, $index + 1);
            $className = "";
            while (strpos($temp, '_') !== false) {
                $index = strpos($temp, '_');
                $className .= ucfirst(mb_strtolower(substr($temp, 0, $index)));
                $temp = substr($temp, $index + 1);
            }
            $className .= ucfirst(mb_strtolower($temp));

            return $namespaceName . $className . "Module";
        } else {
            return null;
        }
    }

    public static function getModuleDescriptor($moduleClassName) {

        try {
            $moduleClass = new ReflectionClass($moduleClassName);
            return $moduleClass;
        } catch (Exception|Error $e) {
            // skip it
        }
    }
}

?>