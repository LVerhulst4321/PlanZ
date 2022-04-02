<?php
// Copyright (c) 2018 Peter Olszowka. All rights reserved. See copyright document for more details.
$report = [];
$report['name'] = 'Conflict Report - Scheduled Panels without Enough People';
$report['multi'] = 'true';
$report['output_filename'] = 'conflict_not_enough_people.csv';
$report['description'] = 'This report runs against scheduled Panels only. Panels generally should have at least 3 panelists.';
$report['categories'] = array(
    'Conflict Reports' => 140,
);
$report['queries'] = [];
$report['queries']['sessions'] =<<<'EOD'
SELECT
        S.sessionid,
        S.title,
        T.trackname,
        R.roomname,
        SCH.roomid,
        DATE_FORMAT(S.duration,'%i') AS durationmin,
        DATE_FORMAT(S.duration,'%k') AS durationhrs,
        DATE_FORMAT(ADDTIME('$ConStartDatim$',SCH.starttime),'%a %l:%i %p') AS starttime,
        IFNULL(COUNT(POS.badgeid), 0) AS numAssigned
    FROM
                  Sessions S
             JOIN Schedule SCH USING (sessionid)
             JOIN Tracks T USING (trackid)
             JOIN Rooms R USING (roomid)
        LEFT JOIN ParticipantOnSession POS USING (sessionid)
    WHERE
            S.statusid IN (1, 2, 3, 6, 7) ## Brainstorm, Vetted, Scheduled, Edit Me, Assigned
        AND S.typeid = 1 ## Panel
    GROUP BY
        S.sessionid, S.title, T.trackname, R.roomname, SCH.roomid, durationmin, durationhrs, starttime
    HAVING
        numAssigned < 3
    ORDER BY
        T.trackname, SCH.starttime;
EOD;
$report['xsl'] =<<<'EOD'
<?xml version="1.0" encoding="UTF-8" ?>
<xsl:stylesheet version="1.1" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
    <xsl:output encoding="UTF-8" indent="yes" method="html" />
    <xsl:include href="xsl/reportInclude.xsl" />
    <xsl:template match="/">
        <xsl:choose>
            <xsl:when test="doc/query[@queryName='sessions']/row">
                <table id="reportTable" class="table table-sm table-bordered">
                    <thead>
                        <tr>
                            <th>Track</th>
                            <th>Session ID</th>
                            <th>Title</th>
                            <th>Room</th>
                            <th>Start time</th>
                            <th>Duration</th>
                            <th>How Many Assigned</th>
                        </tr>
                    </thead>
                    <tbody>
                        <xsl:apply-templates select="doc/query[@queryName='sessions']/row"/>
                    </tbody>
                </table>
            </xsl:when>
            <xsl:otherwise>
                <div class="alert alert-danger">No results found.</div>
            </xsl:otherwise>
        </xsl:choose>
    </xsl:template>

    <xsl:template match="doc/query[@queryName='sessions']/row">
        <tr>
            <td><xsl:value-of select="@trackname"/></td>
            <td>
                <xsl:call-template name="showSessionid">
                    <xsl:with-param name="sessionid" select="@sessionid" />
                </xsl:call-template>
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
            <td><xsl:value-of select="@starttime"/></td>
            <td>
                <xsl:call-template name="showDuration">
                    <xsl:with-param name="durationHrs" select = "@durationHrs" />
                    <xsl:with-param name="durationMin" select = "@durationMin" />
                </xsl:call-template>
            </td>
            <td><xsl:value-of select="@numAssigned"/></td>
        </tr>
    </xsl:template>
</xsl:stylesheet>
EOD;
