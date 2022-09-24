<?php
// Copyright (c) 2022 The PlanZ Development Team. All rights reserved. See copyright document for more details.
// This function provides REST API support the custom text table. Its functionality is similar to the
// populateCustomTextArray() function in /db_functions.php

class CustomText {
    public $data;

    public static function findByPageName($db, $pageName) {
        $query = <<<EOD
        SELECT tag, textcontents FROM CustomText WHERE page = ?;
EOD;
       
        $stmt = mysqli_prepare($db, $query);
        mysqli_stmt_bind_param($stmt, "s", $pageName);
        if (mysqli_stmt_execute($stmt)) {
            $text = new CustomText();
            $text->data = array();
            $resultSet = mysqli_stmt_get_result($stmt);
            while ($row = mysqli_fetch_object($resultSet)) {
                $text->data[$row->tag] = $row->textcontents;
            }
            mysqli_free_result($resultSet);
            mysqli_stmt_close($stmt);
            return $text;
        } else {
            throw new DatabaseSqlException("Query could not be executed: $query");
        }
    }
}

?>