<?php
// Copyright (c) 2018-2019 Peter Olszowka. All rights reserved. See copyright document for more details.
$report = [];
$report['name'] = 'Conflict Report - Participants Scheduled Outside Available Times';
$report['multi'] = 'true';
$report['output_filename'] = 'conflict_part_outside_times.csv';
$report['description'] = 'Show all participant-sessions scheduled outside set of times participant has listed as being available.';
$report['categories'] = array(
    'Conflict Reports' => 90,
);
$report['columns'] = array(
    array(),
    array("orderData" => 2),
    array("visible" => false),
    array("orderData" => 4),
    array("visible" => false),
    array(),
    array(),
    array(),
    array(),
    array("orderData" => 10),
    array("visible" => false),
    array("orderData" => 12),
    array("visible" => false),
    array()
);
$report['queries'] = [];
$report['queries']['participants'] =<<<'EOD'
SELECT 
        FOO.badgeid,
        P.pubsname,
        TR.trackname,
        FOO.sessionid,
        FOO.title,
        R.roomid,
        R.roomname,
        DATE_FORMAT(ADDTIME('$ConStartDatim$',FOO.starttime),'%a %l:%i %p') AS starttime,
        DATE_FORMAT(ADDTIME('$ConStartDatim$',FOO.endtime),'%a %l:%i %p') AS endtime,
        FOO.hours,
        CONCAT(CD.firstname,' ',CD.lastname) AS name,
        CONCAT(CD.lastname, CD.firstname) AS nameSort,
        IF(instr(P.pubsname, CD.lastname) > 0, CD.lastname, substring_index(P.pubsname, ' ', -1)) AS pubsnameSort,
        FOO.starttime AS starttimeSort,
        FOO.endtime AS endtimeSort
    FROM
            (SELECT
                    SCHD.badgeid,
                    SCHD.trackid,
                    SCHD.sessionid,
                    SCHD.starttime,
                    SCHD.endtime, 
                    SCHD.roomid,
                    PAT.availabilitynum,
                    HRS.hours,
                    SCHD.title
                FROM
                            (SELECT
                                    POS.badgeid,
                                    SCH.sessionid,
                                    SCH.starttime,
                                    SCH.roomid,
                                    ADDTIME(SCH.starttime,S.duration) AS endtime, 
                                    S.trackid,
                                    S.title
                                FROM
                                         Schedule SCH
                                    JOIN ParticipantOnSession POS USING (Sessionid)
                                    JOIN Sessions S USING (Sessionid)
                            ) AS SCHD 
                    LEFT JOIN ParticipantAvailabilityTimes PAT ON
                            SCHD.badgeid = PAT.badgeid 
                        AND SCHD.starttime >= PAT.starttime 
                        AND SCHD.endtime <= PAT.endtime 
                    LEFT JOIN 
                        (SELECT badgeid, SUM(HOUR(SUBTIME(endtime,starttime))) AS hours 
                            FROM ParticipantAvailabilityTimes 
                            GROUP BY badgeid
                        ) AS HRS ON SCHD.badgeid = HRS.badgeid
                HAVING PAT.availabilitynum IS NULL
            ) AS FOO
        JOIN Tracks TR USING (trackid)
        JOIN Participants P USING (badgeid)
        JOIN Rooms R USING (roomid)
        JOIN CongoDump CD USING (badgeid)
    HAVING
        FOO.hours IS NOT NULL 
    ORDER BY
        P.sortedpubsname;
EOD;
$report['xsl'] =<<<'EOD'
<?xml version="1.0" encoding="UTF-8" ?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
    <xsl:output encoding="UTF-8" indent="yes" method="xml" />
    <xsl:include href="xsl/reportInclude.xsl" />
    <xsl:template match="/">
        <xsl:choose>
            <xsl:when test="doc/query[@queryName='participants']/row">
                <table id="reportTable" class="table table-sm table-bordered">
                    <thead>
                        <tr style="height:2.8em;">
                            <th>Person ID</th>
                            <th>Pubs Name</th>
                            <th></th>
                            <th>Name</th>
                            <th></th>
                            <th>Track</th>
                            <th>Session ID</th>
                            <th>Title</th>
                            <th>Room</th>
                            <th>Start Time</th>
                            <th></th>
                            <th>End Time</th>
                            <th></th>
                            <th>Total Hours<br />Available</th>
                        </tr>
                    </thead>
                    <tbody>
                        <xsl:apply-templates select="doc/query[@queryName='participants']/row" /> 
                    </tbody>
                </table>
            </xsl:when>
            <xsl:otherwise>
                <div class="alert alert-danger">No results found.</div>
            </xsl:otherwise>
        </xsl:choose>
    </xsl:template>

    <xsl:template match="/doc/query[@queryName='participants']/row" >
        <tr>
            <td>
                <a href="generateReport.php?reportName=schedpartavail.php#badgeid{@badgeid}">
                    <img class="icon-info-sign getSessionInfo" />
                </a>
                <xsl:text> </xsl:text>
                <xsl:call-template name="showBadgeid"><xsl:with-param name="badgeid" select="@badgeid"/></xsl:call-template>
            </td>
            <td><xsl:value-of select="@pubsname" /></td>
            <td><xsl:value-of select="@pubsnameSort" /></td>
            <td><xsl:value-of select="@name" /></td>
            <td><xsl:value-of select="@nameSort" /></td>
            <td><xsl:value-of select="@trackname" /></td>
            <td>
                <xsl:call-template name="showSessionid"><xsl:with-param name="sessionid" select = "@sessionid" /></xsl:call-template>
            </td>
            <td>
                <xsl:call-template name="showSessionTitle">
                    <xsl:with-param name="sessionid" select = "@sessionid" />
                    <xsl:with-param name="title" select = "@title" />
                </xsl:call-template>
            </td>
            <td>
                <xsl:call-template name="showRoomName">
                    <xsl:with-param name="roomid" select = "@roomid" />
                    <xsl:with-param name="roomname" select = "@roomname" />
                </xsl:call-template>
            </td>
            <td style="white-space:nowrap"><xsl:value-of select="@starttime" /></td>
            <td><xsl:value-of select="@starttimeSort" /></td>
            <td style="white-space:nowrap"><xsl:value-of select="@endtime" /></td>
            <td><xsl:value-of select="@endtimeSort" /></td>
            <td><xsl:value-of select="@hours" /></td>
        </tr>
    </xsl:template>
</xsl:stylesheet>
EOD;
