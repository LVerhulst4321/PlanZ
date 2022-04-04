<?php

class VolunteerShift {

    public $id;
    public $job;
    public $minPeople;
    public $maxPeople;

    function __construct($id, $job, $minPeople, $maxPeople) {
        $this->id = $id;
        $this->job = $job;
        $this->minPeople = $minPeople;
        $this->maxPeople = $maxPeople;
    }

    public static function findAll($db) {
        $query = <<<EOD
        SELECT
                S.id as id,
                S.min_volunteer_count,
                S.max_volunteer_count,
                J.id as job_id,
                J.job_name,
                J.is_online,
                J.job_description
            FROM
                volunteer_shift S
            JOIN volunteer_job J ON (J.id = S.volunteer_job_id);
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
                $record = new VolunteerShift($row->id, $job, $row->min_volunteer_count, $row->max_volunteer_count);
                $records[] = $record;
            }
            mysqli_stmt_close($stmt);
            return $records;
        } else {
            throw new DatabaseSqlException("Query could not be executed: $query");
        }
    }

    public static function fromJson($json) {
        $job = new VolunteerJob(null, $json["name"], $json["isOnline"], $json["description"]);
        return $job;
    }

    public static function persist($db, $volunteerJob) {
        $query = <<<EOD
        INSERT INTO volunteer_job
                (job_name, is_online, job_description)
         VALUES (?, ?, ?);
        EOD;
        
        $stmt = mysqli_prepare($db, $query);
        $isOnline = $volunteerJob->isOnline ? 1 : 0;
        mysqli_stmt_bind_param($stmt, "sis", $volunteerJob->name, $isOnline, $volunteerJob->description);
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
            "maxPeople" => $this->maxPeople);
    }
}
?>