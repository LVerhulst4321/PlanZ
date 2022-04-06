<?php

class VolunteerShift {

    public $id;
    public $job;
    public $minPeople;
    public $maxPeople;
    public $location;
    public $fromTime;
    public $toTime;

    function __construct($id, $job, $minPeople, $maxPeople, $fromTime, $toTime, $location) {
        $this->id = $id;
        $this->job = $job;
        $this->minPeople = $minPeople;
        $this->maxPeople = $maxPeople;
        $this->location = $location;
        $this->fromTime = $fromTime;
        $this->toTime = $toTime;
    }

    public static function findAll($db) {
        $query = <<<EOD
        SELECT
                S.id as id,
                S.min_volunteer_count,
                S.max_volunteer_count,
                S.from_time,
                S.to_time,
                S.location,
                J.id as job_id,
                J.job_name,
                J.is_online,
                J.job_description
            FROM
                volunteer_shift S
            JOIN volunteer_job J ON (J.id = S.volunteer_job_id)
           ORDER BY S.from_time, J.job_name;
        EOD;
        
        $stmt = mysqli_prepare($db, $query);
        $records = [];
        $jobs = array();
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            while ($row = mysqli_fetch_object($result)) {
                $job_id = $row->job_id;
                $job = new VolunteerJob($job_id, $row->job_name, $row->is_online ? true : false, $row->job_description);
                if (array_key_exists($job_id, $jobs)) {
                    $job = $jobs[$job_id];
                } else {
                    $jobs[$job_id] = $job;
                }
                $record = new VolunteerShift($row->id, $job, $row->min_volunteer_count, $row->max_volunteer_count, 
                    convert_database_date_to_date($row->from_time), convert_database_date_to_date($row->to_time),
                    $row->location);
                $records[] = $record;
            }
            mysqli_stmt_close($stmt);
            return $records;
        } else {
            throw new DatabaseSqlException("Query could not be executed: $query");
        }
    }

    public static function fromJson($json) {
        $job = new VolunteerJob($json['job'], null, null, null);
        $shift = new VolunteerShift(null, $job, $json['minPeople'], $json['maxPeople'], 
            convert_iso_date_to_date($json['fromTime']), 
            convert_iso_date_to_date($json['toTime']), $json['location']);
        return $shift;
    }

    public static function persist($db, $volunteerShift) {
        $query = <<<EOD
        INSERT INTO volunteer_shift
                (volunteer_job_id, from_time, to_time, location, min_volunteer_count, max_volunteer_count)
         VALUES (?, ?, ?, ?, ?, ?);
        EOD;
        
        $stmt = mysqli_prepare($db, $query);
        $fromTime = $volunteerShift->fromTime->format("Y-m-d H:i:s");
        $toTime = $volunteerShift->toTime->format("Y-m-d H:i:s");
        mysqli_stmt_bind_param($stmt, "isssii", $volunteerShift->job->id, $fromTime, $toTime, 
            $volunteerShift->location, $volunteerShift->minPeople, $volunteerShift->maxPeople);
        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
        } else {
            throw new DatabaseSqlException("Query could not be executed: $query");
        }
    }

    function asArray() {
        return array("id" => $this->id, 
            "job" => $this->job ? $this->job->asArray() : null, 
            "minPeople" => $this->minPeople, 
            "maxPeople" => $this->maxPeople,
            "fromTime" => ($this->fromTime ? $this->fromTime->format('c') : null),
            "toTime" => ($this->toTime ? $this->toTime->format('c') : null),
            "location" => $this->location
        );
    }
}
?>