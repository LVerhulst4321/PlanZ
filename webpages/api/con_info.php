<?php

class ConInfo {

    public $id;
    public $name;
    public $startDate;
    public $endDate;

    function __construct($id, $name, $startDate, $endDate) {
        $this->id = $id;
        $this->name = $name;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    function asJson() {
        return array("id" => $this->id, "name" => $this->name,
            "startDate" => $this->startDate, "endDate" => $this->endDate);
    }

    public static function asJsonList($cons) {
        $result = array();
        foreach ($cons as $con) {
            $result[] = $con->asJson();
        }
        return array("list" => $result);        
    }

    public static function findCurrentCon($db) {
        $query = <<<EOD
        SELECT 
               id, name, con_start_date, con_end_date
          FROM 
               current_con;
EOD;
       
        $stmt = mysqli_prepare($db, $query);
        if (mysqli_stmt_execute($stmt)) {
            $resultSet = mysqli_stmt_get_result($stmt);
            if (mysqli_num_rows($resultSet) == 1) {
                $dbobject = mysqli_fetch_object($resultSet);
                mysqli_stmt_close($stmt);
                $result = new ConInfo($dbobject->id, $dbobject->name, $dbobject->con_start_date, $dbobject->con_end_date);
                return $result;
            } else {
                throw new DatabaseException("Expected one result, but found " . mysqli_num_rows($resultSet));
            }
        } else {
            return new DatabaseSqlException("Query could not be executed: $query");
        }
    }
}

?>