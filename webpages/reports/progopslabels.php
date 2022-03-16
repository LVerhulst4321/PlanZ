<?php
$report = [];
$report['name'] = 'CSV -- Full Participant Schedule for Program Ops Labels';
$report['description'] = 'Export CSV file of full participant schedule for Program Ops labels';
$report['categories'] = array(
    'Reports downloadable as CSVs' => 90,
    'Program Ops Reports' => 40
);
$report['csv_output'] = true;
$report['group_concat_expand'] = true;
$report['queries'] = [];
$report['queries']['master'] =<<<'EOD'
SELECT
        POS.badgeid,
        CD.badgenumber,
        P.pubsname,
        A25.value AS pronouns,
        GROUP_CONCAT(
            DATE_FORMAT(ADDTIME('$ConStartDatim$',SCH.starttime),'%a, %l:%i %p'),"-",
            DATE_FORMAT(ADDTIME('$ConStartDatim$',ADDTIME(SCH.starttime,S.duration)),'%l:%i %p')," - ",
            R.roomname, " - ",
            S.title,
            IF(moderator=1,'(M)','')
            ORDER BY SCH.starttime
            SEPARATOR "\n") panelinfo
    FROM
            Participants P
       JOIN ParticipantOnSession POS USING (badgeid)
       JOIN CongoDump CD USING (badgeid)
       JOIN Sessions S USING (sessionid)
       JOIN Schedule SCH USING (sessionid)
       JOIN Rooms R USING (roomid)
  LEFT JOIN ParticipantSurveyAnswers A25 ON P.badgeid = A25.participantid AND A25.questionid = 25
    GROUP BY
        P.badgeid
    ORDER BY
        CD.lastname, CD.firstname
EOD;
$report['output_filename'] = 'progopslabels.csv';
$report['column_headings'] = 'personid,badgenumber,pubs name,pronouns,panel info';

