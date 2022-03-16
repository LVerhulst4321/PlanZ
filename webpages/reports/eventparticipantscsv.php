<?php
// Copyright (c) 2011-2019 Peter Olszowka. All rights reserved. See copyright document for more details.
$report = [];
$report['name'] = 'Event Participants';
$report['description'] = 'Export CSV file of event (division) schedule with participants';
$report['categories'] = array(
    'Reports downloadable as CSVs' => 50,
    'Events Reports' => 40
);
$report['csv_output'] = true;
$report['group_concat_expand'] = false;
$report['queries'] = [];
$report['queries']['master'] =<<<'EOD'
SELECT
        S.title,
        S.sessionid,
        R.roomname,
        DATE_FORMAT(ADDTIME('$ConStartDatim$', SCH.starttime),'%a') as day, 
        DATE_FORMAT(ADDTIME('$ConStartDatim$', SCH.starttime),'%l:%i %p') as time, 
        CONCAT(
            IF(
                LEFT(duration,2)=00, '',
                IF(
                    LEFT(duration,1)=0,
                    CONCAT(RIGHT(LEFT(duration,2),1), 'hr '),
                    CONCAT(LEFT(duration,2),'hr ')
                )
            ),
            IF(
                DATE_FORMAT(duration,'%i')=00, '',
                IF(
                    LEFT(DATE_FORMAT(duration,'%i'),1)=0,
                    CONCAT(RIGHT(DATE_FORMAT(duration,'%i'),1),'min'),
                    CONCAT(DATE_FORMAT(duration,'%i'),'min')
                )
            )
        ) as duration,
        T.trackname,
        P.pubsname,
        CD.firstname,
        CD.lastname
    FROM
             Schedule SCH
        JOIN Sessions S USING (sessionid)
        JOIN Rooms R USING (roomid)
        JOIN Tracks T USING (trackid)
        JOIN ParticipantOnSession POS USING (sessionid)
        JOIN Participants P USING (badgeid)
        JOIN CongoDump CD USING (badgeid)
    WHERE
        S.divisionid = 3; /* Events */
EOD;
$report['output_filename'] = 'event_schedule_participants.csv';
$report['column_headings'] = 'title,sessionid,room,day,time,duration,track,"pubs name", "last name", "first name"';
