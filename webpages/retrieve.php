<?php
// Copyright (c) 2011-2020 Peter Olszowka. All rights reserved. See copyright document for more details.
function retrieveSessions($sessionSearchArray) {
    global $linki;
    $ConStartDatim = CON_START_DATIM; // make it a variable so it can be substituted
    $sessionEditCodes = '1,2,6';   // 1=created in brainstorm, 2=Created in staff create session, 6=Created by import
    $query = <<<EOB
SELECT
        S.sessionid,
        TR.trackname,
        TY.typename,
        S.title,
        concat( if(left(S.duration,2)=00, '', if(left(S.duration,1)=0, concat(right(left(S.duration,2),1),'hr '), 
            concat(left(S.duration,2),'hr '))), if(date_format(S.duration,'%i')=00, '', if(left(date_format(S.duration,'%i'),1)=0, 
            concat(right(date_format(S.duration,'%i'),1),'min'), concat(date_format(S.duration,'%i'),'min')))) AS duration,
        S.estatten,
        S.progguiddesc,
        S.persppartinfo,
        DATE_FORMAT(ADDTIME('$ConStartDatim',SCH.starttime),'%a %l:%i %p') AS starttime,
        R.roomname,
        SS.statusname,
        GROUP_CONCAT(TA.tagname SEPARATOR ', ') AS taglist,
        notesforprog,
        notesforpart,
        servicenotes,
        pubstatusname,
        concat('Made by: ', SEH.name, ' Email: ', SEH.email_address) sessionhistory
    FROM
                  Sessions S
             JOIN Tracks TR USING (trackid)
             JOIN Types TY USING (typeid)
             JOIN SessionStatuses SS USING (statusid)
             JOIN SessionEditHistory SEH USING (sessionid)
        LEFT JOIN Schedule SCH USING (sessionid)
        LEFT JOIN Rooms R USING (roomid)
        LEFT JOIN PubStatuses PS USING (pubstatusid)
        LEFT JOIN SessionHasTag SHT USING (sessionid)
        LEFT JOIN Tags TA USING (tagid)
    WHERE
        SEH.sessioneditcode IN ($sessionEditCodes)
EOB;
    if (isset($sessionSearchArray['trackidList'])) {
        $trackidList = $sessionSearchArray['trackidList'];
        if (($trackidList != 0) and ($trackidList != "")) {
            $query .= " AND TR.trackid in ($trackidList)";
        }
    }
    if (isset($sessionSearchArray['tagidArray']) and count($sessionSearchArray['tagidArray']) > 0) {
        $tagidArray = $sessionSearchArray['tagidArray'];
        // AND EXISTS (SELECT * FROM SessionHasTag 
        if (isset($sessionSearchArray['tagmatch']) && $sessionSearchArray['tagmatch']=='all') {
            foreach ($tagidArray as $tag) {
                $query .= " AND EXISTS (SELECT * FROM SessionHasTag WHERE sessionid = S.sessionid AND tagid = $tag)";
            }
        } else {
            $tagidList = implode(',', $tagidArray);
            $query .= " AND EXISTS (SELECT * FROM SessionHasTag WHERE sessionid = S.sessionid AND tagid IN ($tagidList))";
        }
    }
    if (isset($sessionSearchArray['statusidList'])) {
        $statusidList = $sessionSearchArray['statusidList'];
        if (($statusidList != 0) and ($statusidList != '')) {
            $query .= " AND SS.statusid in ($statusidList)";
        }
    }
    if (isset($sessionSearchArray['sessionid'])) {
        $sessionid = $sessionSearchArray['sessionid'];
        if (($sessionid != 0) and ($sessionid != '')) {
            $query .= " AND S.sessionid = $sessionid";
        }
    }
    if (isset($sessionSearchArray['divisionid'])) {
        $divisionid = $sessionSearchArray['divisionid'];
        if (($divisionid != 0) and ($divisionid != '')) {
            $query .= " AND S.divisionid = $divisionid";
        }
    }
    if (isset($sessionSearchArray['typeidList'])) {
        $typeidList = $sessionSearchArray['typeidList'];
        if (($typeidList != 0) and ($typeidList != '')) {
            $query .= " AND S.typeid in ($typeidList)";
        }
    }
    if (isset($sessionSearchArray['searchTitle'])) {
        $searchTitle = $sessionSearchArray['searchTitle'];
        if ($searchTitle != '') {
            $searchTitle = mysqli_real_escape_string($linki, $searchTitle);
            $query .= " AND S.title like \"%$searchTitle%\"";
        }
    }
    if (isset($sessionSearchArray['pubstatusid'])) {
        $pubstatusid = $sessionSearchArray['pubstatusid'];
        if (($pubstatusid != 0) and ($pubstatusid != '')) {
            $query .= " AND S.pubstatusid = $pubstatusid";
        }
    }
    if (isset($sessionSearchArray['hashtag'])) {
        $hashtag = $sessionSearchArray['hashtag'];
        if ($hashtag != '') {
            $hashtag = strtolower(mysqli_real_escape_string($linki, $hashtag));
            $query .= " AND LOWER(S.hashtag) like \"%$hashtag%\"";
        }
    }
    $query .= "\n";
    $query .= "GROUP BY S.sessionid,\n";
    $query .= "         TR.trackname,\n";
    $query .= "         TY.typename,\n";
    $query .= "         S.title,\n";
    $query .= "         duration,\n";
    $query .= "         S.estatten,\n";
    $query .= "         S.progguiddesc,\n";
    $query .= "         S.persppartinfo,\n";
    $query .= "         starttime,\n";
    $query .= "         R.roomname,\n";
    $query .= "         SS.statusname,\n";
    $query .= "         notesforprog,\n";
    $query .= "         notesforpart,\n";
    $query .= "         servicenotes,\n";
    $query .= "         pubstatusname,\n";
    $query .= "         sessionhistory\n";
    $query .= " ORDER BY ";
    $query .= "  trackname, typename, title";
    return(mysqli_query_exit_on_error($query));
}

?>
