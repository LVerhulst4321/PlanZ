<?php
// Copyright (c) 2018 Peter Olszowka. All rights reserved. See copyright document for more details.
$report = [];
$report['name'] = 'Conflict Report - Assigned v. Scheduled issue';
$report['multi'] = 'true';
$report['output_filename'] = 'conflict_assigned_v_sched.csv';
$report['description'] = 'These are sessions that are either in the grid and have no one assigned or they have people assigned and are not in the grid.';
$report['categories'] = array(
    'Conflict Reports' => 10,
);
$report['queries'] = [];
$report['queries']['sessions'] =<<<'EOD'
SELECT
        T.trackname,
        TY.typename,
        S.sessionid,
        S.title,
        IFNULL(Subq1.numInterested, 0) AS numInterested,
        R.roomname,
        SCH.roomid,
        IFNULL(Subq2.numAssigned, 0) AS numAssigned, 
        DATE_FORMAT(ADDTIME('$ConStartDatim$',SCH.starttime),'%a %l:%i %p') AS starttime
    FROM
                  Sessions S
             JOIN Tracks T USING (trackid)
             JOIN Types TY USING (typeid)
        LEFT JOIN Schedule SCH USING (sessionid)
        LEFT JOIN (
                    SELECT
                            PSI.sessionid,
                            COUNT(*) AS numInterested
                        FROM
                                 ParticipantSessionInterest PSI
                            JOIN Participants P USING (badgeid)
                        WHERE
                            P.interested = 1
                        AND ((PSI.rank is not NULL
                            AND PSI.rank != 0) OR PSI.willmoderate = 1)
                        GROUP BY PSI.sessionid
                  ) AS Subq1 USING (sessionid)
        LEFT JOIN (
                    SELECT
                            POS.sessionid,
                            COUNT(*) AS numAssigned
                        FROM
                                 ParticipantOnSession POS
                            JOIN Participants P USING (badgeid)
                        WHERE
                            P.interested = 1
                        GROUP BY POS.sessionid
                  ) AS Subq2 USING (sessionid)
        LEFT JOIN Rooms R USING (roomid)
    WHERE
            (   S.statusid IN (1, 2, 3, 6, 7) /* Brainstorm, Vetted, Scheduled, Edit Me, Assigned) */
            AND SCH.scheduleid IS NULL
            AND IFNULL(numAssigned, 0) != 0
            )
         OR (   SCH.scheduleid IS NOT NULL
            AND IFNULL(numAssigned, 0) = 0
            )
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
                            <th>Type</th>
                            <th>Session ID</th>
                            <th>Title</th>
                            <th>Room</th>
                            <th>Start Time</th>
                            <th>Num. part's assigned</th>
                            <th>Num. part's interested</th>
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
            <td><xsl:value-of select="@trackname" /></td>
            <td><xsl:value-of select="@typename" /></td>
            <td>
                <xsl:call-template name="showSessionid">
                    <xsl:with-param name="sessionid" select = "@sessionid" />
                </xsl:call-template>
            </td>
            <td>
                <xsl:call-template name="showSessionTitle">
                    <xsl:with-param name="sessionid" select = "@sessionid" />
                    <xsl:with-param name="title" select = "@title" />
                </xsl:call-template>
            </td>
            <xsl:choose>
                <xsl:when test="@roomid">
                    <td>
                        <xsl:call-template name="showRoomName">
                            <xsl:with-param name="roomid" select = "@roomid" />
                            <xsl:with-param name="roomname" select = "@roomname" />
                        </xsl:call-template>
                    </td>
                    <td><xsl:value-of select="@starttime" /></td>
                </xsl:when>
                <xsl:otherwise>
                    <td colspan="2">Not scheduled</td>
                </xsl:otherwise>
            </xsl:choose>
            <td><xsl:value-of select="@numAssigned" /></td>
            <td><xsl:value-of select="@numInterested" /></td>
        </tr>
    </xsl:template>
</xsl:stylesheet>
EOD;
