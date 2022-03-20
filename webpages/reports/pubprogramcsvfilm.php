<?php
$report = [];
$report['name'] = 'CSV -- Panel Report for Pubs Film';
$report['description'] = 'Export CSV file of full public schedule for publications. Uses special participant label field. Film and Anime only.';
$report['categories'] = array(
    'Reports downloadable as CSVs' => 90,
    'Publication Reports' => 40
);
$report['csv_output'] = true;
$report['group_concat_expand'] = true;
$report['queries'] = [];
$report['queries']['master'] =<<<'EOD'
SELECT
        S.sessionid,
        DATE_FORMAT(ADDTIME('$ConStartDatim$',SCH.starttime),'%W') AS startday,
        DATE_FORMAT(ADDTIME('$ConStartDatim$',SCH.starttime),'%l:%i %p') AS starttm,
        DATE_FORMAT(ADDTIME('$ConStartDatim$',ADDTIME(SCH.starttime, S.duration)),'%W') AS endday,
        DATE_FORMAT(ADDTIME('$ConStartDatim$',ADDTIME(SCH.starttime, S.duration)),'%l:%i %p') AS endtm,
        R.roomname,
        D.divisionname AS DIVISION,
        T.trackname AS TRACK,
        Ty.typename AS TYPE,
        S.title,
        S.progguidhtml AS 'Long Text',
        IF(LENGTH(GROUP_CONCAT(P.pubsname SEPARATOR '')>0),CONCAT(S.participantlabel,':'),'') AS 'partLabel',
        GROUP_CONCAT(' ',P.pubsname, IF (POS.moderator=1,' (M)','') ORDER BY P.sortedpubsname) AS 'PARTIC'
    FROM
                  Sessions S
             JOIN Schedule SCH USING (sessionid)
             JOIN Rooms R USING (roomid)
             JOIN Divisions D ON (D.divisionid = S.divisionid)
             JOIN Tracks T USING (trackid)
             JOIN Types Ty USING (typeid)
             JOIN KidsCategories K USING (kidscatid)
        LEFT JOIN ParticipantOnSession POS ON SCH.sessionid=POS.sessionid 
        LEFT JOIN Participants P ON POS.badgeid=P.badgeid
    WHERE
            S.pubstatusid != 3  ## no Do Not Print
        AND D.divisionid in (7, 12)  ## film and anime
    GROUP BY
        SCH.sessionid, SCH.starttime, R.roomname, R.display_order
    ORDER BY
        SCH.starttime,
        R.display_order
EOD;
$report['output_filename'] = 'pubprogram.csv';
$report['column_headings'] = 'sessionid,dayName,startTime,endDayName,endTime,location,division,track,type,eventName,description,participantlabel,participants';
$report['replaceString'] = '<br />';

