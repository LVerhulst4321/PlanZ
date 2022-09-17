<?php

if (!include ('../../config/db_name.php')) {
    include ('../../config/db_name.php');
}
require_once('../../db_exceptions.php');
require_once('../db_support_functions.php');
require_once('../participant_functions.php');
require_once('../jwt_functions.php');
require_once('../con_info.php');


function convert_database_date_to_date($db_date) {
    if ($db_date) {
        $date = date_create_from_format('Y-m-d H:i:s', $db_date);
        $date->setTimezone(new DateTimeZone(PHP_DEFAULT_TIMEZONE));
        return $date;
    } else {
        return null;
    }
}

function read_division_and_track_options($db) {
	$query = <<<EOD
	SELECT d.divisionid, d.divisionname, d.display_order, 
		   t.trackid, t.trackname, t.display_order as track_order,
		   k.from_time, k.to_time 
	 FROM Divisions d
	 LEFT OUTER JOIN con_key_dates k ON (d.external_key = k.external_key AND k.con_id = (select min(id) from current_con) )
	 JOIN Tracks t ON (d.divisionid = t.divisionid)
	WHERE t.divisionid = d.divisionid
	  AND d.brainstorm_support = 'Y'
	ORDER BY d.display_order, d.divisionid, track_order;
EOD;
   
	$stmt = mysqli_prepare($db, $query);
	if (mysqli_stmt_execute($stmt)) {
		$result = mysqli_stmt_get_result($stmt);
		$options = array();
		$current_division = null;
		while ($row = mysqli_fetch_object($result)) {

			if ($current_division == null || $current_division['id'] !== $row->divisionid) {
				if ($current_division != null) {
					$options[] = $current_division;
				}
				$from_time = convert_database_date_to_date($row->from_time);
				$to_time = convert_database_date_to_date($row->to_time);
				$current_division = array(
					"id" => $row->divisionid,
					"name" => $row->divisionname,
					"from_time" => $from_time ? date_format($from_time, "c") : null,
					"to_time" => $to_time ? date_format($to_time, "c") : null,
					"tracks" => array()
				);
			}
			$track = array(
				"trackid" => $row->trackid,
				"trackname" => $row->trackname
			);
			array_push($current_division['tracks'], $track);
		}
		mysqli_stmt_close($stmt);
		if ($current_division != null) {
			$options[] = $current_division;
		}
		return $options;
	} else {
		throw new DatabaseSqlException($query);
	}
}

function create_jwt_for_badgeid($db, $badgeid) {
	$query = <<<EOD
 SELECT 
        P.password, P.data_retention, P.badgeid, C.firstname, C.lastname, C.badgename, C.regtype 
   FROM 
        Participants P 
   JOIN CongoDump C USING (badgeid)
  WHERE 
         P.badgeid = ?;
 EOD;

	$stmt = mysqli_prepare($db, $query);
	mysqli_stmt_bind_param($stmt, "s", $badgeid);
	if (mysqli_stmt_execute($stmt)) {
		$result = mysqli_stmt_get_result($stmt);
		if (mysqli_num_rows($result) == 1) {
			$dbobject = mysqli_fetch_object($result);
			mysqli_stmt_close($stmt);
			return jwt_create_token($dbobject->badgeid, get_name($dbobject), $dbobject->regtype == null ? false : true);
		} else {
			return false;
		}
	} else {
		throw new DatabaseSqlException($query);
	}
}

$db = connect_to_db();
session_start();
try {
	$currentCon = ConInfo::findCurrentCon($db);

	$options = read_division_and_track_options($db);
	$result = array("divisions" => $options, "con" => $currentCon->asJson());

	// create JWT if already logged in
	if (isset($_SESSION['badgeid'])) {
		$jwt = create_jwt_for_badgeid($db, $_SESSION['badgeid']);
		if ($jwt) {
			header("Authorization: Bearer ".$jwt);
		}
	}

	header('Content-type: application/json');
	$json_string = json_encode($result);
    echo $json_string;

} finally {
	$db->close();
}

?>