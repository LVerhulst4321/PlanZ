<?php
// Copyright (c) 2009-2019 Peter Olszowka. All rights reserved. See copyright document for more details.
$report = [];
$report['name'] = 'Palm Calendar';
$report['description'] = 'Export CSV file for Palm device calendars';
$report['categories'] = array(
    'Reports downloadable as CSVs' => 60,
    'Publication Reports' => 40
);
$report['csv_output'] = true;
$report['group_concat_expand'] = true;
$report['queries'] = [];
$report['queries']['master'] =<<<'EOD'
SELECT
        DATE_FORMAT(ADDTIME('$ConStartDatim$',starttime),'%a') AS 'Day',
        DATE_FORMAT(ADDTIME('$ConStartDatim$',starttime),'%l:%i %p') AS 'Start Time',
        LEFT(S.duration,5) Length,
        R.Roomname,
        T.trackname as Track,
        S.Title,
        IF(GROUP_CONCAT(P.pubsname) is NULL,'',GROUP_CONCAT(P.pubsname SEPARATOR ', ')) AS 'Participants'
    FROM
                Rooms R
           JOIN Schedule SCH USING (roomid)
           JOIN Sessions S USING (sessionid)
      LEFT JOIN Tracks T USING (trackid)
      LEFT JOIN ParticipantOnSession POS ON SCH.sessionid=POS.sessionid
      LEFT JOIN Participants P ON POS.badgeid=P.badgeid
    WHERE
        S.pubstatusid = 2
    GROUP BY
        SCH.sessionid, SCH.starttime, R.Roomname
    ORDER BY
        SCH.starttime, R.roomname;
EOD;
$report['output_filename'] = 'PDASchedule.csv';
$report['column_headings'] = 'day,start time,duration,room name,track,title,participants';
