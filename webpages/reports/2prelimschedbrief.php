<?php
// Copyright (c) 2018-2019 Peter Olszowka. All rights reserved. See copyright document for more details.
$report = [];
$report['name'] = 'Preliminary Programming Schedule';
$report['multi'] = 'true';
$report['output_filename'] = 'preliminary_schedule.csv';
$report['description'] = 'Preliminary list of all scheduled "Programming" Division sessions.';
$report['categories'] = array(
    'Programming Reports' => 10,
);
$report['columns'] = array(
    array("orderData" => 1),
    array("visible" => false),
    array("orderData" => 3),
    array("visible" => false),
    null,
    null,
    null,
    null,
    null,
    null
);
$report['queries'] = [];
$report['queries']['schedule'] =<<<'EOD'
SELECT
        DATE_FORMAT(ADDTIME('$ConStartDatim$',SCH.starttime),'%a %l:%i %p') AS starttime,
        ADDTIME('$ConStartDatim$',SCH.starttime) AS startTimeSort,
        T.trackname, S.sessionid, S.title, R.roomname, SCH.roomid,
        DATE_FORMAT(S.duration,'%i') as durationmin,
        DATE_FORMAT(S.duration,'%k') as durationhrs,
        S.duration AS durationSort,
	(SELECT SEC.description FROM SessionEditCodes SEC WHERE SEC.sessioneditcode =
            (SELECT SEH.sessioneditcode FROM SessionEditHistory SEH WHERE SEH.sessionid = S.sessionid AND
                SEH.timestamp = (SELECT MAX(timestamp) FROM SessionEditHistory WHERE sessionid = S.sessionid)
            )
         ) AS description,
        (SELECT MAX(timestamp) FROM SessionEditHistory WHERE sessionid = S.sessionid) AS timestamp
    FROM
             Sessions S
        JOIN Schedule SCH USING (sessionid)
        JOIN Tracks T USING (trackid)
        JOIN Rooms R USING (roomid)
    WHERE
        S.divisionid = 2
    ORDER BY
        T.trackname,
        SCH.starttime;
EOD;
$report['xsl'] =<<<'EOD'
<?xml version="1.0" encoding="UTF-8" ?>
<xsl:stylesheet version="1.1" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
    <xsl:output encoding="UTF-8" indent="yes" method="html" />
    <xsl:include href="xsl/reportInclude.xsl" />
    <xsl:template match="/">
        <xsl:choose>
            <xsl:when test="doc/query[@queryName='schedule']/row">
                <table id="reportTable" class="table table-sm table-bordered">
                    <thead>
                        <tr style="height:2.6rem">
                            <th>Start Time</th>
                            <th></th>
                            <th>Duration</th>
                            <th></th>
                            <th>Room Name</th>
                            <th>Track Name</th>
                            <th>Session ID</th>
                            <th>Title</th>
                            <th>When Changed</th>
                            <th>Change Type</th>
                        </tr>
                    </thead>
                    <tbody>
                        <xsl:apply-templates select="/doc/query[@queryName='schedule']/row"/>
                    </tbody>
                </table>
            </xsl:when>
            <xsl:otherwise>
                <div class="alert alert-danger">No results found.</div>
            </xsl:otherwise>
        </xsl:choose>
    </xsl:template>
    <xsl:template match="/doc/query[@queryName='schedule']/row">
        <tr>
            <td><xsl:value-of select="@starttime" /></td>
            <td><xsl:value-of select="@startTimeSort" /></td>
            <td>
                <xsl:call-template name="showDuration">
                    <xsl:with-param name="durationhrs" select = "@durationhrs" />
                    <xsl:with-param name="durationmin" select = "@durationmin" />
                </xsl:call-template>
            </td>
            <td><xsl:value-of select="@durationSort" /></td>
            <td>
                <xsl:call-template name="showRoomName">
                    <xsl:with-param name="roomid" select = "@roomid" />
                    <xsl:with-param name="roomname" select = "@roomname" />
                </xsl:call-template>
            </td>
            <td><xsl:value-of select="@trackname" /></td>
            <td><xsl:call-template name="showSessionid"><xsl:with-param name="sessionid" select = "@sessionid" /></xsl:call-template></td>
            <td>
                <xsl:call-template name="showSessionTitle">
                    <xsl:with-param name="sessionid" select = "@sessionid" />
                    <xsl:with-param name="title" select = "@title" />
                </xsl:call-template>
            </td>
            <td><xsl:value-of select="@timestamp" /></td>
            <td><xsl:value-of select="@description" /></td>
        </tr>
    </xsl:template>
</xsl:stylesheet>
EOD;
