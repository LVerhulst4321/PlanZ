<?php

if (!include ('../../config/db_name.php')) {
    include ('../../config/db_name.php');
}

require_once('../../db_exceptions.php');
require_once('../db_support_functions.php');
require_once('../../name.php');
require_once('../../time_slot_functions.php');
require_once(__DIR__ . "/../../room_model.php");

define("ATTEND_IN_PERSON", 1);
define("ATTEND_EITHER", 2);
define("ATTEND_ONLINE", 3);

// Find the X most popular panels...
define("NUMBER_OF_SESSIONS", 100);

// How many panelists should we assign?
define("MIN_NUMBER_OF_PANELISTS", 3);
define("IDEAL_NUMBER_OF_PANELISTS", 4);
define("MAX_NUMBER_OF_PANELISTS", 5);


class PersonData {
    public $badgeId;
    public $name;
    public $schedulingPreferences;
    public $rankings;
    public $availability;
    public $assignments;

    function hasSessionRank($sessionId) {
        return array_key_exists($sessionId, $this->rankings);
    }
    function rankForSession($sessionId) {
        return $this->hasSessionRank($sessionId) ? $this->rankings[$sessionId] : null;
    }
    function assignmentLuck() {
        $luck = 0;
        $numberOfAssignments = 0;
        foreach ($this->assignments as $a) {
            $rank = $this->rankForSession($a->sessionId);
            $luck += ($rank) ? ($rank->rank * $rank->rank) : 0;
            $numberOfAssignments += ($rank) ? 1 : 0;
        }
        return $luck / sqrt($this->schedulingPreferences->overallMax - $numberOfAssignments);
    }

    // TODO: consider already assigned items
    function isAvailable($timeSlot) {
        if (count($this->availability) == 0) {
            return true;
        } else if ($this->schedulingPreferences->maxSessionsForDay($timeSlot->day) <= $this->sessionCountForDay($timeSlot->day)) {
            return false;
        } else if ($this->overlapsExistingAssignments($timeSlot)) {
            return false;
        } else {
            $result = false;
            foreach ($this->availability as $a) {
                $result = $result || $a->contains($timeSlot->day * 96 + time_to_row_index($timeSlot->startTime), $timeSlot->day * 96 + time_to_row_index($timeSlot->endTime));
            }
            return $result;
        }
    }

    function overlapsExistingAssignments($timeSlot) {
        $result = false;
        foreach ($this->assignments as $a) {
            if ($a->timeSlot) {
                $result = $result || $a->timeSlot->overlaps($timeSlot);
            }
        }
        return $result;
    }

    function sessionCountForDay($day) {
        $count = 0;
        foreach ($this->assignments as $a) {
            if ($a->timeSlot != null) {
                $count += ($a->timeSlot->day == $day) ? 1 : 0;
            }
        }
        return $count;
    }
}

class SchedulingPreferences {
    public $overallMax;
    public $dailyMaxes;

    function maxSessionsForDay($day) {
        if (count($this->dailyMaxes) === 0) {
            return PREF_DLY_SESNS_LMT;
        } else {
            $result = PREF_DLY_SESNS_LMT;
            $hasRealValue = false;
            foreach ($this->dailyMaxes as $max) {
                if ($max != null && $max != 0) {
                    $hasRealValue;
                }
            }
            if ($hasRealValue) {
                $temp = array_key_exists($day, $this->dailyMaxes) ? $this->dailyMaxes[$day] : null;
                return ($temp == null) ? PREF_DLY_SESNS_LMT : $temp;
            } else {
                return $result;
            }
        }
    }
}

class Ranking {
    public $sessionId;
    public $rank;
    public $willModerate;
    public $howAttend;
}

class Availability {
    public $start;
    public $end;

    function contains($startIndex, $endIndex) {
        $thisStartIndex = time_to_row_index($this->start);
        $thisEndIndex = time_to_row_index($this->end);

        return ($thisStartIndex <= $startIndex && $thisEndIndex >= $startIndex
            && $thisStartIndex <= $endIndex && $thisEndIndex >= $endIndex);
    }
}

class Participation {
    public $participant;
    public $moderator;
}

class Session {
    public $sessionId;
    public $rank;
    public $timeSlot;
    public $potentialParticipants;
    public $assignedParticipants;
    public $message;
    public $type;

    function potentialOnlineParticipants() {
        $result = array();
        foreach ($this->potentialParticipants as $p) {
            $rank = $p->rankForSession($this->sessionId);
            if ($rank && ($rank->howAttend == ATTEND_ONLINE || $rank->howAttend == ATTEND_EITHER)) {
                $result[] = $p;
            }
        }
        return $result;
    }

    function potentialOnlineParticipantCount() {
        return count($this->potentialOnlineParticipants());
    }

    function potentialInPersonParticipants() {
        $result = array();
        foreach ($this->potentialParticipants as $p) {
            $rank = $p->rankForSession($this->sessionId);
            if ($rank && ($rank->howAttend == ATTEND_IN_PERSON || $rank->howAttend == ATTEND_EITHER)) {
                $result[] = $p;
            }
        }
        return $result;
    }

    function potentialInPersonParticipantCount() {
        return count($this->potentialInPersonParticipants());
    }

    function allParticipantsAvailable($timeSlot) {
        $result = true;
        foreach ($this->assignedParticipants as $p) {
            $result &= $p->participant->isAvailable($timeSlot);
        }
        return $result;
    }
}

class TimeSlot {
    public $day;
    public $startTime;
    public $endTime;
    public $roomId;
    public $room;
    public $session;

    function startTimeIndex() {
        return $this->day * 96 + time_to_row_index($this->startTime);
    }

    function endTimeIndex() {
        return $this->day * 96 + time_to_row_index($this->endTime);
    }

    function overlaps($timeSlot) {
        if ($this->startTimeIndex() <= $timeSlot->startTimeIndex() &&
            $timeSlot->startTimeIndex() <= $this->endTimeIndex()) {
            // we contain the other start time
            return true;
        } else if ($this->startTimeIndex() <= $timeSlot->endTimeIndex() &&
            $timeSlot->endTimeIndex() <= $this->endTimeIndex()) {
            // we contain the other end time
            return true;
        } else if ($timeSlot->startTimeIndex() <= $this->startTimeIndex() &&
            $this->endTimeIndex() <= $timeSlot->endTimeIndex()) {
            // the other timeslot encompasses us
            return true;
        } else {
            return false;
        }
    }

    static function compareByPreferredTime($ts1, $ts2) {
        $time1 = time_to_row_index($ts1->startTime);
        $time2 = time_to_row_index($ts2->startTime);

        if ($time1 < 40 && $time2 >= 40) {
            return 1;
        } else if ($time2 < 40 && $time1 >= 40) {
            return -1;
        } else if ($time1 < 40 && $time2 < 40) {
            if ($ts1->day == $ts2->day) {
                return $ts1->roomId - $ts2->roomId;
            } else {
                return $ts1->day - $ts2->day;
            }
        } else if ($time1 > 88 && $time2 > 88) {
            if ($time1 != $time2) {
                return $time1 - $time2;
            } else if ($ts1->day != $ts2->day) {
                return $ts1->day - $ts2->day;
            } else {
                return $ts1->roomId - $ts2->roomId;
            }
        } else if ($time1 > 88) {
            return 1;
        } else if ($time2 > 88) {
            return -1;
        } else if ($time1 > 60 && $time2 > 60) {
            if ($time1 != $time2) {
                return $time1 - $time2;
            } else if ($ts1->day != $ts2->day) {
                return $ts1->day - $ts2->day;
            } else {
                return $ts1->roomId - $ts2->roomId;
            }
        } else if ($time1 > 60) {
            return 1;
        } else if ($time2 > 60) {
            return -1;
        } else if ($time1 != $time2) {
            return $time1 - $time2;
        } else if ($ts1->day != $ts2->day) {
            return $ts1->day - $ts2->day;
        } else {
            return $ts1->roomId - $ts2->roomId;
        }
    }
}


function find_all_interested_participants($db) {
    $query = <<<EOD
     select P.badgeid, PA.maxprog as overall_max,
            P.pubsname, CD.badgename, CD.firstname, CD.lastname,
            PAD.day, PAD.maxprog as day_max
        FROM Participants P
        JOIN CongoDump CD using (badgeid)
        LEFT OUTER JOIN ParticipantAvailability PA using (badgeid)
        LEFT OUTER JOIN ParticipantAvailabilityDays PAD using (badgeid)
       WHERE P.interested = 1
         AND P.badgeid in (select badgeid from ParticipantSessionInterest where rank in (1, 2, 3) or willmoderate = 1)
       ORDER BY P.badgeid, PAD.day;
EOD;
    $stmt = mysqli_prepare($db, $query);
    if (mysqli_stmt_execute($stmt)) {
        $participants = array();
        $current = null;
        $result = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_object($result)) {
            $badgeId = $row->badgeid;
            if ($current == null || $current->badgeId != $badgeId) {
                $current = new PersonData();
                $current->badgeId = $badgeId;
                $name = new PersonName();
                $name->pubsName = $row->pubsname;
                $name->badgeName = $row->badgename;
                $name->firstName = $row->firstname;
                $name->lastName = $row->lastname;
                $current->name = $name;
                $current->schedulingPreferences = new SchedulingPreferences();
                $current->rankings = array();
                $current->availability = array();
                $current->assignments = array();
                if ($row->overall_max) {
                    $current->schedulingPreferences->overallMax = $row->overall_max;
                } else {
                    $current->schedulingPreferences->overallMax = PREF_TTL_SESNS_LMT;
                }
                $current->schedulingPreferences->dailyMaxes = array();
                $participants[$badgeId] = $current;
            }
            if ($row->day != null && $row->day_max != null) {
                $participants[$badgeId]->schedulingPreferences->dailyMaxes[$row->day] = $row->day_max;
            }
        }
        mysqli_stmt_close($stmt);
        return $participants;
    } else {
        throw new DatabaseSqlException("Query could not be executed: $query");
    }
}

function find_all_rankings($db, $participants) {
    $query = <<<EOD
      SELECT PSI.badgeid, PSI.sessionid, PSI.rank, PSI.willmoderate, PSI.attend_type
        FROM Sessions s
        JOIN ParticipantSessionInterest PSI USING (sessionid)
        JOIN SessionStatuses ss USING (statusid)
        JOIN PubStatuses ps USING (pubstatusid)
       WHERE ss.may_be_scheduled = 1
         AND (PSI.rank in (1, 2, 3) or PSI.willmoderate = 1)
         AND ps.pubstatusname = 'Public'
         AND s.divisionid in (select divisionid from Divisions where divisionname = 'Panels')
EOD;

    $stmt = mysqli_prepare($db, $query);
    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_object($result)) {
            $badgeId = $row->badgeid;
            $ranking = new Ranking();
            $ranking->sessionId = $row->sessionid;
            $ranking->rank = $row->rank;
            if ($ranking->rank == null) {
                $ranking->rank = 3; // some people specify that they will moderate, but don't specify a rank
            }
            $ranking->willModerate = ($row->willmoderate == 1) ? true : false;
            $ranking->howAttend = $row->attend_type;

            $participant = $participants[$badgeId];
            if ($participant) {
                $participant->rankings[$ranking->sessionId] = $ranking;
            } else {
                error_log("Badge id $badgeId not found.");
            }
        }

        mysqli_stmt_close($stmt);
        return $participants;
    } else {
        throw new DatabaseSqlException("Query could not be executed: $query");
    }
}

function find_availability($db, $participants) {
    $query = <<<EOD
      SELECT PAT.badgeid, PAT.starttime, PAT.endtime
        FROM ParticipantAvailabilityTimes PAT
        JOIN Participants P USING (badgeid)
       WHERE PAT.badgeid in (select badgeid from ParticipantSessionInterest where rank in (1, 2, 3) or willmoderate = 1)
         AND P.interested = 1
       ORDER BY PAT.badgeid, PAT.availabilitynum
EOD;

    $stmt = mysqli_prepare($db, $query);
    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_object($result)) {
            $badgeId = $row->badgeid;
            $availability = new Availability();
            $availability->start = $row->starttime;
            $availability->end = $row->endtime;

            $participant = $participants[$badgeId];
            if ($participant) {
                $participant->availability[] = $availability;
            }
        }

        mysqli_stmt_close($stmt);
        return $participants;
    } else {
        throw new DatabaseSqlException("Query could not be executed: $query");
    }
}

function find_sessions($db, $numberOfSessions) {
    $query = <<<EOD
        select sessionid, title,
            sum(attend1 * 1.2 + attend2 + attend3 * 0.5) as rank
        from
        (SELECT
                S.sessionid, S.title, T.trackname, T.display_order,
                case PSI.attend when 1 then 1 else 0 end as attend1,
                case PSI.attend when 2 then 1 else 0 end as attend2,
                case PSI.attend when 3 then 1 else 0 end as attend3,
                P.badgeid
            FROM
                Sessions S
                JOIN Tracks T USING (trackid)
                JOIN Types Ty USING (typeid)
            LEFT JOIN ParticipantSessionInterest PSI USING (sessionid)
            LEFT JOIN Participants P ON PSI.badgeid = P.badgeid AND P.interested = 1
            WHERE
                S.statusid IN (2,3,7)
                AND S.invitedguest = 0
                AND S.divisionid in (select divisionid from Divisions where divisionname = 'Panels')) FB
        GROUP BY
            sessionid, title
        ORDER BY rank desc, sessionid
        LIMIT $numberOfSessions
EOD;

    $sessions = array();
    $stmt = mysqli_prepare($db, $query);
    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_object($result)) {
            $sessionId = $row->sessionid;
            $session = new Session();
            $session->sessionId = $sessionId;
            $session->title = $row->title;
            $session->rank = $row->rank;
            $session->potentialParticipants = array();
            $session->assignedParticipants = array();
            $sessions[$sessionId] = $session;
        }

        mysqli_stmt_close($stmt);
        return $sessions;
    } else {
        throw new DatabaseSqlException("Query could not be executed: $query");
    }
}

function find_all_timeslots($db) {
    $query = <<<EOD
    SELECT r.roomid, r2a.day, s.start_time, s.end_time, r.roomname, r.is_online
      FROM Rooms r,
           room_to_availability r2a,
           room_availability_schedule a,
           room_availability_slot s,
           Divisions d
    WHERE r.is_scheduled = 1
      AND r.roomid = r2a.roomid
      AND r2a.availability_id = a.id
      AND s.availability_schedule_id = a.id
      AND d.divisionid = s.divisionid
      AND d.divisionname = 'Panels';
EOD;

    $rooms = array();
    $slots = array();
    $stmt = mysqli_prepare($db, $query);
    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_object($result)) {
            $slot = new TimeSlot();
            $slot->roomId = $row->roomid;

            if (array_key_exists($slot->roomId, $rooms)) {
                $slot->room = $rooms[$slot->roomId];
            } else {
                $room = new Room();
                $room->roomId = $row->roomid;
                $room->roomName = $row->roomname;
                $room->isOnline = $row->is_online == 'Y' ? true : false;
                $slot->room = $room;
                $rooms[$slot->roomId] = $room;
            }

            $slot->day = $row->day;
            $slot->startTime = $row->start_time;
            $slot->endTime = $row->end_time;
            $slot->roomName = $row->roomname;
            $slots[] = $slot;
        }

        mysqli_stmt_close($stmt);
        return $slots;
    } else {
        throw new DatabaseSqlException("Query could not be executed: $query");
    }
}

function collate_persons_into_sessions($sessions, $participants) {
    foreach ($sessions as $session) {
        foreach ($participants as $participant) {
            if ($participant->hasSessionRank($session->sessionId)) {
                $session->potentialParticipants[] = $participant;
            }
        }
    }
}

function sort_by_number_of_potential_participants($s1, $s2) {
    $diff = count($s1->potentialParticipants) - count($s2->potentialParticipants);
    if ($diff === 0) {
        return strcmp($s1->sessionId, $s2->sessionId);
    } else {
        return $diff;
    }
}

function review_potential_participants($session, $participants) {
    $result = array();
    foreach ($participants as $p) {
        if (count($p->assignments) < $p->schedulingPreferences->overallMax) {
            $result[] = $p;
        }
    }
    return $result;
}

function assign_all_participants($session, $participants) {
    foreach ($participants as $p) {
        $participation = new Participation();
        $participation->participant = $p;
        $session->assignedParticipants[$p->badgeId] = $participation;
        $p->assignments[] = $session;
    }
}

function sort_by_highest_ranking($i1, $i2) {
    $sessionId = $i1["session"]->sessionId;
    $rank1 = $i1["participant"]->rankForSession($sessionId);
    $rank2 = $i2["participant"]->rankForSession($sessionId);

    // top rank is weighted pretty high
    if ($rank1->rank != $rank2->rank && ($rank1->rank == 1 || $rank2->rank == 1)) {
        return $rank1->rank - $rank2->rank;
    } else {
        $luck1 = -($i1["participant"]->assignmentLuck() / sqrt($rank1->rank));
        $luck2 = -($i2["participant"]->assignmentLuck() / sqrt($rank2->rank));

        if ($luck1 != $luck2) {
            return $luck1 - $luck2;
        } else {
            return strcmp($i1["participant"]->badgeId, $i2["participant"]->badgeId);
        }
    }
}

function choose_best_matches_and_assign($session, $participants) {
    $temp = array();
    foreach ($participants as $p) {
        $temp[] = array("session" => $session, "participant" => $p);
    }

    usort($temp, "sort_by_highest_ranking");

    for ($i = 0; $i < IDEAL_NUMBER_OF_PANELISTS && $i < count($temp); $i++) {
        $winnowedList[] = $temp[$i]["participant"];
    }

    assign_all_participants($session, $winnowedList);
}

function process_participants($session, $type) {
    $session->type = $type;
    $potential = review_potential_participants($session, $type == ATTEND_IN_PERSON ? $session->potentialInPersonParticipants() : $session->potentialOnlineParticipants());
    if (count($potential) < MIN_NUMBER_OF_PANELISTS) {
        $typeText = attend_type_as_text($type);
        $session->message = "Not enough filtered $typeText participants";
    } else if (count($potential) <= IDEAL_NUMBER_OF_PANELISTS) {
        assign_all_participants($session, $potential);
    } else {
        choose_best_matches_and_assign($session, $potential);
    }
}

function create_first_pass_assignments_for_sessions($sessions, $participants) {

    $simple_sessions = array();
    foreach ($sessions as $session) {
        $simple_sessions[] = $session;
    }

    // sort sessions
    usort($simple_sessions, "sort_by_number_of_potential_participants");

    foreach ($simple_sessions as $session) {
        if (count($session->potentialParticipants) < MIN_NUMBER_OF_PANELISTS) {
            $session->message = "Not enough potential panelists to make a panel";
        } else if ($session->potentialOnlineParticipantCount() < MIN_NUMBER_OF_PANELISTS && $session->potentialInPersonParticipantCount() < MIN_NUMBER_OF_PANELISTS) {
            $session->message = "Not enough potential panelists of the same attendance type";
        } else if ($session->potentialOnlineParticipantCount() < MIN_NUMBER_OF_PANELISTS) {
            process_participants($session, ATTEND_IN_PERSON);
        } else if ($session->potentialInPersonParticipantCount() < MIN_NUMBER_OF_PANELISTS) {
            process_participants($session, ATTEND_ONLINE);
        } else {
            // determine if only online or only in-person is viable
            $potentialInPerson = review_potential_participants($session, $session->potentialInPersonParticipants());
            $potentialOnline = review_potential_participants($session, $session->potentialOnlineParticipants());
            if (count($potentialInPerson) < MIN_NUMBER_OF_PANELISTS && count($potentialOnline) < MIN_NUMBER_OF_PANELISTS) {
                $session->message = "Not enough filtered potential panelists of the same attendance type";
            } else if (count($potentialOnline) < MIN_NUMBER_OF_PANELISTS) {
                process_participants($session, ATTEND_IN_PERSON);
            } else if (count($potentialInPerson) < MIN_NUMBER_OF_PANELISTS) {
                process_participants($session, ATTEND_ONLINE);
            } else {
                // determine if online or in-person is better
                if ((count($potentialOnline) * 1.3) > count($potentialInPerson)) {
                    process_participants($session, ATTEND_ONLINE);
                } else {
                    process_participants($session, ATTEND_IN_PERSON);
                }
            }
        }
    }

    return $simple_sessions;
}

function attend_type_as_text($type) {
    if ($type == ATTEND_ONLINE) {
        return "Online";
    } else if ($type == ATTEND_IN_PERSON) {
        return "In-Person";
    } else {
        return null;
    }

}

function assign_online_timeslots($sessions, $timeslots) {
    $filteredSessions = array();
    foreach ($sessions as $s) {
        if (count($s->assignedParticipants) > 0 && $s->type == ATTEND_ONLINE) {
            $filteredSessions[] = $s;
        }
    }

    $filteredSlots = array();
    foreach ($timeslots as $s) {
        if ($s->room->isOnline) {
            $filteredSlots[] = $s;
        }
    }

    foreach ($filteredSessions as $s) {
        foreach ($filteredSlots as $ts) {
            if ($ts->session == null && $s->allParticipantsAvailable($ts)) {
                $ts->session = $s;
                $s->timeSlot = $ts;
                break;
            }
        }
    }
}


function assign_timeslots($db, $sessions) {
    $timeSlots = find_all_timeslots($db);
    usort($timeSlots, array("TimeSlot", "compareByPreferredTime"));

    assign_online_timeslots($sessions, $timeSlots);

    return $timeSlots;
}

session_start();
$db = connect_to_db();
try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['badgeid'])) {

        $participants = find_all_interested_participants($db);
        $participants = find_all_rankings($db, $participants);
        $participants = find_availability($db, $participants);

        $sessions = find_sessions($db, NUMBER_OF_SESSIONS);
        collate_persons_into_sessions($sessions, $participants);

        $result = create_first_pass_assignments_for_sessions($sessions, $participants);

        $timeSlots = assign_timeslots($db, $result);

        // create a JSON output
        $records = array();
        foreach ($result as $s) {
            if ($s->timeSlot != null) {
                $record = array("sessionId" => $s->sessionId, "count" => count($s->potentialParticipants), "message" => $s->message, "type" => attend_type_as_text($s->type));
                $assignments = array();
                foreach ($s->assignedParticipants as $p) {
                    $assignments[] = array("badgeid" => $p->participant->badgeId, "name" => $p->participant->name->getBadgeName());
                }
                $record["assignments"] = $assignments;

                $timeSlot = array("Room" => $s->timeSlot->roomName, "day" => $s->timeSlot->day, "startTime" => $s->timeSlot->startTime, "endTime" => $s->timeSlot->endTime);
                $record["timeSlot"] = $timeSlot;
                $records[] = $record;
            }
        }

        $temp = array();
        foreach ($participants as $p) {
            $record = array("participantId" => $p->badgeId, "count" => count($p->rankings), "name" => $p->name->getBadgeName(), "maxPanels" => $p->schedulingPreferences->overallMax);
            $rankings = array();
            foreach ($p->rankings as $r) {
                $rankings[] = $r->sessionId;
            }
            $record["rankings"] = $rankings;
            $temp[] = $record;
        }

        $slots = array();
        foreach ($timeSlots as $ts) {
            $record = array("room" => $ts->room->roomName, "day" => $ts->day, "start" => $ts->startTime, "end" => $ts->endTime);
            $slots[] = $record;
        }


        header('Content-type: application/json; charset=utf-8');
        $json_string = json_encode(array("sessions" => $records));
        echo $json_string;

    } else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        http_response_code(401);
    } else {
        http_response_code(405);
    }
} finally {
    $db->close();
}
?>