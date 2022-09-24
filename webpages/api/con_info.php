<?php

class ConInfo {

    public $id;
    public $name;
    public $startDate;
    public $endDate;
    public $perennialName;
    public $websiteUrl;

    function __construct($id, $name, $startDate, $endDate, $perennialName = null, $websiteUrl = null) {
        $this->id = $id;
        $this->name = $name;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->perennialName = $perennialName;
        $this->websiteUrl = $websiteUrl;
    }

    function asJson() {
        $result = array("id" => $this->id, "name" => $this->name,
            "startDate" => $this->startDate, "endDate" => $this->endDate);
        if ($this->perennialName != null && $this->websiteUrl != null) {
            $result["links"] = array("website" => array("name" => $this->perennialName, "url" => $this->websiteUrl));
        }
        return $result;
    }

    public static function asJsonList($cons) {
        $result = array();
        foreach ($cons as $con) {
            $result[] = $con->asJson();
        }
        return array("list" => $result);        
    }

    public static function findCurrentCon($db, $checkEnvironment = true) {
        $query = <<<EOD
        SELECT 
               id, name, con_start_date, con_end_date, perennial_name, website_url
          FROM 
               current_con;
EOD;
       
        $stmt = mysqli_prepare($db, $query);
        if (mysqli_stmt_execute($stmt)) {
            $resultSet = mysqli_stmt_get_result($stmt);
            if (mysqli_num_rows($resultSet) == 1) {
                $dbobject = mysqli_fetch_object($resultSet);
                mysqli_stmt_close($stmt);
                $result = new ConInfo($dbobject->id, $dbobject->name, $dbobject->con_start_date, $dbobject->con_end_date, $dbobject->perennial_name, $dbobject->website_url);
                return $result;
            } else if ($checkEnvironment) {
                mysqli_stmt_close($stmt);
                $data = ConData::fromEnvironmentDefinition();
                return new ConInfo(null, $data->name, $data->startDate, $data->endDate);
            } else {
                throw new DatabaseException("Expected one result, but found " . mysqli_num_rows($resultSet));
            }
        } else {
            throw new DatabaseSqlException("Query could not be executed: $query");
        }
    }
}

?>