<?php
// Copyright (c) 2018 Peter Olszowka. All rights reserved. See copyright document for more details.
$report = [];
$report['name'] = 'Schedule';
$report['multi'] = 'true';
$report['output_filename'] = 'full_print_schedule.csv';
$report['description'] = 'Show the full printable schedule. Excludes Do Not Print items.';
$report['categories'] = array(
    'Programming Reports' => 60,
    'Program Ops Reports' => 1,
);
$report['queries'] = [];
$report['queries']['schedule'] =<<<'EOD'
SELECT
        DATE_FORMAT(ADDTIME('$ConStartDatim$',SCH.starttime),'%a %l:%i %p') as starttime,
        DATE_FORMAT(S.duration,'%i') as durationmin,
        DATE_FORMAT(S.duration,'%k') as durationhrs,
        R.roomname, 
        T.trackname,
        S.sessionid,
        S.title
    FROM
             Schedule SCH
        JOIN Sessions S USING (sessionid)
        JOIN Tracks T USING (trackid)
        JOIN Rooms R USING (roomid)
    WHERE
        S.pubstatusid != 3 # not Do Not Print
    ORDER BY
        SCH.starttime, T.trackname;
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
                        <tr>
                            <th>Start Time</th>
                            <th>Duration</th>
                            <th>Room Name</th>
                            <th>Track Name</th>
                            <th>Session ID</th>
                            <th>Title</th>
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
            <td>
                <xsl:call-template name="showDuration">
                    <xsl:with-param name="durationhrs" select = "@durationhrs" />
                    <xsl:with-param name="durationmin" select = "@durationmin" />
                </xsl:call-template>
            </td>
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
        </tr>
    </xsl:template>
</xsl:stylesheet>
EOD;
