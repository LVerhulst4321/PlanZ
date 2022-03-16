<?php
// Copyright (c) 2009-2019 Peter Olszowka. All rights reserved. See copyright document for more details.
$report = [];
$report['name'] = 'Pocket Program';
$report['description'] = 'Export CSV file of public schedule for generating pocket program';
$report['categories'] = array(
    'Reports downloadable as CSVs' => 80,
    'Publication Reports' => 40
);
$report['csv_output'] = true;
$report['group_concat_expand'] = true;
$report['queries'] = [];
$report['queries']['master'] =<<<'EOD'
SELECT
        S.sessionid,
        DATE_FORMAT(ADDTIME('$ConStartDatim$',SCH.starttime),'%a') AS Day,
        DATE_FORMAT(ADDTIME('$ConStartDatim$',SCH.starttime),'%l:%i %p') AS 'Time',
        CONCAT(IF(LEFT(S.duration,2)=00, '',
                  IF(LEFT(S.duration,1)=0,
                     CONCAT(RIGHT(LEFT(S.duration,2),1), 'hr '),
                     CONCAT(LEFT(S.duration,2),'hr '))),
               IF(DATE_FORMAT(S.duration,'%i')=00, '',
                  IF(LEFT(DATE_FORMAT(S.duration,'%i'),1)=0,
                     CONCAT(RIGHT(DATE_FORMAT(S.duration,'%i'),1),'min'),
                     CONCAT(DATE_FORMAT(S.duration,'%i'),'min')))
               ) Duration,
        R.roomname,
        T.trackname AS TRACK,
        TY.typename AS TYPE,
        K.kidscatname,
        S.title,
        S.progguiddesc AS 'Long Text',
        group_concat(' ',P.pubsname, if (POS.moderator=1,' (m)','')) AS 'PARTIC'
    FROM
                Sessions S
           JOIN Schedule SCH USING (sessionid)
           JOIN Rooms R USING (roomid)
           JOIN Tracks T USING (trackid)
           JOIN Types TY USING (typeid)
           JOIN KidsCategories K USING (kidscatid)
      LEFT JOIN ParticipantOnSession POS ON SCH.sessionid=POS.sessionid 
      LEFT JOIN Participants P ON POS.badgeid=P.badgeid
    WHERE 
        S.pubstatusid = 2
    GROUP BY
        SCH.sessionid
    ORDER BY 
        SCH.starttime, 
        R.roomname;
EOD;
$report['output_filename'] = 'pocketprogram.csv';
$report['column_headings'] = 'sessionid,day,time,duration,room,track,type,"kids category",title,description,participants';
