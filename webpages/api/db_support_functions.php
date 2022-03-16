<?php
// Copyright (c) 2021, 2022 BC Holmes. All rights reserved. See copyright document for more details.
// These functions provide support for common database queries.

class DatabaseException extends Exception {};
class DatabaseSqlException extends DatabaseException {};

function connect_to_db($set_timezone = false) {
    $db = mysqli_connect(DBHOSTNAME, DBUSERID, DBPASSWORD, DBDB);
    if (!$db) {
        throw new DatabaseException("Could not connect to database");
    } else {
        mysqli_set_charset($db, "utf8");
        if (!mysqli_query($db, "SET SESSION sql_mode = ''")) {
            throw new DatabaseSqlException("Could not SET SESSION sql_mode");
        }

        if ($set_timezone && DB_DEFAULT_TIMEZONE != "") {
            $query = "SET time_zone = '" . DB_DEFAULT_TIMEZONE . "';";
            if (!mysqli_query($db, $query)) {
                throw new DatabaseSqlException("Could not process timezone change: $query");
            }
        }

        return $db;
    }
}

?>