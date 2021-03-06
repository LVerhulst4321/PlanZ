<?php
// Copyright (c) 2009-2019 Peter Olszowka. All rights reserved. See copyright document for more details.
$report = [];
$report['name'] = 'Long Description';
$report['description'] = 'Export CSV file of yet another full public schedule';
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
        T.trackname,
        TY.typename,
        D.divisionname,
        PS.pubstatusname,
        S.pubsno,
        GROUP_CONCAT(TG.tagname SEPARATOR ' ') tags,
        K.kidscatname,
        S.title,
        S.progguiddesc AS 'Description'
    FROM
                  Schedule SCH
             JOIN Sessions S USING (sessionid)
             JOIN Tracks T USING (trackid)
             JOIN Types TY USING (typeid)
             JOIN Divisions D ON (D.divisionid = S.divisionid)
             JOIN PubStatuses PS USING (pubstatusid)
             JOIN KidsCategories K USING (kidscatid)
        LEFT JOIN SessionHasTag SHT USING (sessionid)
        LEFT JOIN Tags TG USING (tagid)
    WHERE
        PS.pubstatusname = 'Public'
    GROUP BY
        SCH.scheduleid
EOD;
$report['output_filename'] = 'longdesc.csv';
$report['column_headings'] = 'sessionid,track,type,division,"publication status",pubsno,tags,"kids category",title,description';
