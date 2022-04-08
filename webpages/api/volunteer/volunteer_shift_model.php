<?php

class VolunteerShift {

    public $id;
    public $job;
    public $minPeople;
    public $maxPeople;
    public $location;
    public $fromTime;
    public $toTime;
    public $currentSignupCount;

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
                J.job_description,
                sum((case when PHVS.badgeid is not null then 1 else 0 end)) as signup_count
             FROM volunteer_shift S
             JOIN volunteer_job J ON (J.id = S.volunteer_job_id)
        LEFT JOIN participant_has_volunteer_shift PHVS ON (PHVS.volunteer_shift_id = S.id)
           GROUP BY S.id, S.min_volunteer_count, S.max_volunteer_count, S.from_time,
                    S.to_time, S.location, J.id, J.job_name, J.is_online, J.job_description
           ORDER BY S.from_time, J.job_name;
        EOD;
        
        $stmt = mysqli_prepare($db, $query);
        if (mysqli_stmt_execute($stmt)) {
            $records = VolunteerShift::convertResultSetToShifts($stmt);
            mysqli_stmt_close($stmt);
            return $records;
        } else {
            throw new DatabaseSqlException("Query could not be executed: $query");
        }
    }

    public static function findAllAssignedToParticipant($db, $badgeid) {
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
                J.job_description,
                sum((case when PHVS.badgeid is not null then 1 else 0 end)) as signup_count
            FROM
                volunteer_shift S
            JOIN volunteer_job J ON (J.id = S.volunteer_job_id)
        LEFT JOIN participant_has_volunteer_shift PHVS ON (PHVS.volunteer_shift_id = S.id)
        WHERE S.id in (select volunteer_shift_id from participant_has_volunteer_shift where badgeid = ?)
        GROUP BY S.id, S.min_volunteer_count, S.max_volunteer_count, S.from_time,
                    S.to_time, S.location, J.id, J.job_name, J.is_online, J.job_description
           ORDER BY S.from_time, J.job_name;
        EOD;
        
        $stmt = mysqli_prepare($db, $query);
        mysqli_stmt_bind_param($stmt, "s", $badgeid); 
        if (mysqli_stmt_execute($stmt)) {
            $records = VolunteerShift::convertResultSetToShifts($stmt);
            mysqli_stmt_close($stmt);
            return $records;
        } else {
            throw new DatabaseSqlException("Query could not be executed: $query");
        }
    }

    static function convertResultSetToShifts($stmt) {
        $records = [];
        $jobs = array();
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
            if (isset($row->signup_count)) {
                $record->currentSignupCount = $row->signup_count;
            }
            $records[] = $record;
        }
        return $records;
    }

    public static function fromJson($json) {
        $id = array_key_exists("id", $json) ? $json["id"] : null;
        $job = new VolunteerJob($json['job'], null, null, null);
        $shift = new VolunteerShift($id, $job, $json['minPeople'], $json['maxPeople'], 
            convert_iso_date_to_date($json['fromTime']), 
            convert_iso_date_to_date($json['toTime']), $json['location']);
        return $shift;
    }

    public static function persist($db, $volunteerShift) {
        $fromTime = $volunteerShift->fromTime->format("Y-m-d H:i:s");
        $toTime = $volunteerShift->toTime->format("Y-m-d H:i:s");

        if ($volunteerShift->id == null) {
            $query = <<<EOD
            INSERT INTO volunteer_shift
                    (volunteer_job_id, from_time, to_time, location, min_volunteer_count, max_volunteer_count)
            VALUES (?, ?, ?, ?, ?, ?);
            EOD;
            
            $stmt = mysqli_prepare($db, $query);
            mysqli_stmt_bind_param($stmt, "isssii", $volunteerShift->job->id, $fromTime, $toTime, 
                $volunteerShift->location, $volunteerShift->minPeople, $volunteerShift->maxPeople);
            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_close($stmt);
            } else {
                throw new DatabaseSqlException("Insert could not be executed: $query");
            }
        } else {
            $query = <<<EOD
            UPDATE volunteer_shift
               SET volunteer_job_id = ?, from_time = ?, to_time = ?, location = ?, min_volunteer_count = ?, max_volunteer_count = ?
             WHERE id = ?;
            EOD;
            
            $stmt = mysqli_prepare($db, $query);
            mysqli_stmt_bind_param($stmt, "isssiii", $volunteerShift->job->id, $fromTime, $toTime, 
                $volunteerShift->location, $volunteerShift->minPeople, $volunteerShift->maxPeople, $volunteerShift->id);
            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_close($stmt);
            } else {
                throw new DatabaseSqlException("Insert could not be executed: $query");
            }
        }
    }

    public static function exists($db, $shiftId) {
        $query = <<<EOD
        SELECT
                count(*) as c
            FROM
                volunteer_shift S 
           WHERE id = ?;
        EOD;
        
        $stmt = mysqli_prepare($db, $query);
        mysqli_stmt_bind_param($stmt, "i", $shiftId);
        $exists = 0;
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            while ($row = mysqli_fetch_object($result)) {
                $exists = $row->c;
            }
            mysqli_stmt_close($stmt);
            return $exists > 0;
        } else {
            throw new DatabaseSqlException("Select count(*) command could not be executed: $query");
        }
    }


    public static function deleteAssignment($db, $badgeId, $shiftId) {
        $query = <<<EOD
        DELETE FROM participant_has_volunteer_shift WHERE badgeid = ? and volunteer_shift_id = ?;
        EOD;
        
        $stmt = mysqli_prepare($db, $query);
        mysqli_stmt_bind_param($stmt, "si", $badgeId, $shiftId);
        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
        } else {
            throw new DatabaseSqlException("Delete command could not be executed: $query");
        }
    }

    public static function deleteShift($db, $shiftId) {
        $query = <<<EOD
        DELETE FROM volunteer_shift WHERE volunteer_shift_id = ?;
        EOD;
        
        $stmt = mysqli_prepare($db, $query);
        mysqli_stmt_bind_param($stmt, "i", $shiftId);
        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
        } else {
            throw new DatabaseSqlException("Delete command could not be executed: $query");
        }
    }

    public static function createAssignment($db, $badgeId, $shiftId) {
        $query = <<<EOD
        INSERT INTO participant_has_volunteer_shift (badgeid, volunteer_shift_id) values (?, ?);
        EOD;
        
        $stmt = mysqli_prepare($db, $query);
        mysqli_stmt_bind_param($stmt, "si", $badgeId, $shiftId);
        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
        } else {
            if ($db->errno == 1062) {
                throw new DatabaseDuplicateKeyException("Duplicate key");
            } else {
                throw new DatabaseSqlException("Insert command could not be executed: $query");
            }
        }
    }

    function asArray() {
        return array("id" => $this->id, 
            "job" => $this->job ? $this->job->asArray() : null, 
            "minPeople" => $this->minPeople, 
            "maxPeople" => $this->maxPeople,
            "fromTime" => ($this->fromTime ? $this->fromTime->format('c') : null),
            "toTime" => ($this->toTime ? $this->toTime->format('c') : null),
            "location" => $this->location,
            "currentSignupCount" => $this->currentSignupCount
        );
    }
}
?>