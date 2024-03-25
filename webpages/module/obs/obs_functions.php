<?php

/**
 * Extract OBS feeds.
 *
 * PHP version 7.4+
 *
 * @category Module
 * @package  PlanZ
 * @author   James Shields <james@lostcarpark.com>
 * @license  Zambia Software Licence
 * @link     https://github.com/LVerhulst4321/PlanZ
 */

require_once __DIR__ . '/../../db_functions.php';

/**
 * Write file for room.
 *
 * @param int    $roomId       The room identifier.
 * @param string $filepath     The file to write to.
 * @param array  $fileTags     Array of top level file tags such as room name.
 * @param array  $participants Array of participants per session.
 *
 * @return void
 */
function writeObsRoomFile(int $roomId, string $filepath, array  $fileTags, array $participants): void
{
    $json = json_encode(getObsData("R.roomid = $roomId", $participants, $fileTags));
    writeJsonFile($filepath, $json);
}

/**
 * Write file for room.
 *
 * @param string $operator     The comparison operator to use.
 * @param string $tag          The room identifier.
 * @param string $filepath     The file to write to.
 * @param array  $fileTags     Array of top level file tags such as room name.
 * @param array  $participants Array of participants per session.
 *
 * @return void
 */
function writeObsTagFile(string $operator, string $tag, string $filepath, array $fileTags, array $participants): void
{
    $json = json_encode(getObsData("TA.tagname $operator '$tag'", $participants, $fileTags));
    writeJsonFile($filepath, $json);
}

/**
 * Write the JSON encoded file data to the specified file path.
 *
 * @param string $filepath The path to the file to be written.
 * @param string $json     The JSON data to write.
 *
 * @return void
 */
function writeJsonFile(string $filepath, string $json): void
{
    $file = fopen($filepath, "w");
    if ($file === false) {
        echo "<p>Unable to write to $filepath.</p>";
        return;
    }
    fwrite($file, $json);
    fclose($file);
}

/**
 * Get data for a room and return as an array.
 *
 * @param string $where         SQL criteria to determine sessions to return.
 * @param array  $participants  Array of participants per session.
 * @param array  $fileTags      Array of top level file tags such as room name.
 * @param string $showpubstatus Comma separated list of pub status to include.
 *
 * @return array
 */
function getObsData(
    string $where,
    array $participants,
    array $fileTags = [],
    string $showpubstatus = '2'
): array {
    $ConStartDatim = CON_START_DATIM;

    $query = <<<EOD
        SELECT
            S.sessionid AS id,
            S.title,
            TR.trackname,
            TY.typename,
            R.roomname AS loc,
            R.floor,
            D.divisionname,
            DATE_FORMAT(duration, '%k') * 60 + DATE_FORMAT(duration, '%i') AS mins,
            S.progguiddesc AS `desc`,
            DATE_FORMAT(ADDTIME('$ConStartDatim',SCH.starttime),'%W') as day,
            DATE_FORMAT(ADDTIME('$ConStartDatim',SCH.starttime),'%Y-%m-%d') as date,
            DATE_FORMAT(ADDTIME('$ConStartDatim',SCH.starttime),'%H:%i') as time,
            GROUP_CONCAT(TA.tagname SEPARATOR ',') AS taglist,
            S.meetinglink
        FROM Schedule SCH
            INNER JOIN Sessions S USING (sessionid)
            INNER JOIN Tracks TR USING (trackid)
            INNER JOIN Types TY USING (typeid)
            INNER JOIN Rooms R USING (roomid)
            INNER JOIN Divisions D ON (S.divisionid = D.divisionid)
            LEFT JOIN SessionHasTag SHT USING (sessionid)
            LEFT JOIN Tags TA USING (tagid)
        WHERE
            S.pubstatusid IN ($showpubstatus) /* Public */
            AND $where
            AND (ADDTIME('$ConStartDatim', SCH.starttime) > NOW())
        GROUP BY
            S.sessionid
        ORDER BY
            SCH.starttime, S.sessionid;
    EOD;

    $result = mysqli_query_with_error_handling($query);
    $program = [];
    while ($row = mysqli_fetch_object($result)) {
        // Build array of tags (including track and dicision).
        $tagsArray = ["Track:".$row->trackname, "Division:".$row->divisionname];
        if (!empty($row->typename)) {
            $tagsArray[] = 'Type:'.$row->typename;
        }
        if (!empty($row->taglist)) {
            foreach (explode(',', $row->taglist) as $singletag) {
                array_push($tagsArray, "Tag:".$singletag);
            }
        }
        $programRow = [
            "id"     => $row->id,
            "title"  => $row->title,
            "tags"   => $tagsArray,
            "day"    => $row->day,
            "date"   => $row->date,
            "time"   => $row->time,
            "mins"   => $row->mins,
            "loc"    => empty($row->floor)
                ? [$row->loc]
                : [$row->loc . ' - ' . $row->floor],
            "desc"   => $row->desc,
            "links"  => [],
        ];
        if (array_key_exists($row->id, $participants)) {
            $programRow["people"] = $participants[$row->id];
        }
        if (!empty($row->meetinglink)) {
            $programRow["links"] = ["meeting" => $row->meetinglink];
        }
        $program[] = $programRow;
    }

    return array_merge(
        $fileTags,
        ['program' => $program],
    );
}

/**
 * Return an array of participants on each session.
 *
 * @param string $showpubstatus Comma separated list of pub status to include.
 *
 * @return array
 */
function getParticipants(string $showpubstatus = '2'): array
{
    // first query: which people are on which sessions
    $query = <<<EOD
        SELECT
            SCH.sessionid,
            P.badgeid,
            P.pubsname,
            P.sortedpubsname,
            POS.moderator
        FROM
            Schedule SCH
            INNER JOIN Sessions S USING (sessionid)
            INNER JOIN ParticipantOnSession POS USING (sessionid)
            INNER JOIN Participants P USING (badgeid)
        WHERE
            S.pubstatusid IN ($showpubstatus) /* Public */
        ORDER BY
            SCH.sessionid,
            POS.moderator DESC,
            P.badgeid;
    EOD;
    $result = mysqli_query_with_error_handling($query);

    $sessionParticipants = [];
    while ($row = mysqli_fetch_object($result)) {
        $sessionParticipants[$row->sessionid][] = [
            "id" => $row->badgeid,
            "name" => $row->pubsname,
            "moderator" => $row->moderator
        ];
    }
    return $sessionParticipants;
}
