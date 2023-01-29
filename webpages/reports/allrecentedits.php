<?php
// Copyright (c) 2018 Peter Olszowka. All rights reserved. See copyright document for more details.
$report = [];
$report['name'] = 'All Recent Edits';
$report['multi'] = 'true';
$report['output_filename'] = 'allRecentEdits.csv';
$report['description'] = 'All edits to sessions, session participants or the schedule made within the past 10 days.';
$report['categories'] = array(
    'Events Reports' => 80,
    'Programming Reports' => 80,
    'GOH Reports' => 80,
    'Publication Reports' => 80
);
$report['queries'] = [];
$report['queries']['change'] =<<<'EOD'
SELECT
        SHIST.change_by_badgeid, SHIST.description,
        P.pubsname,
        CD.badgename,
        CONCAT(CD.firstname, " ", CD.lastname) as name,
        TR.trackname, S.title, SS.statusname, SCH.roomid, R.roomname, S.sessionid,
        DATE_FORMAT(ADDTIME('$ConStartDatim$',SCH.starttime),'%a %l:%i %p') AS starttime,
        DATE_FORMAT(SHIST.change_ts, "%c/%e/%y %l:%i %p") AS change_ts_format
    FROM
                session_change_history SHIST
             JOIN Sessions S using (sessionid)
             JOIN Tracks TR using (trackid)
             JOIN SessionStatuses SS using (statusid)
             JOIN Participants P ON (SHIST.change_by_badgeid = P.badgeid)
             JOIN CongoDump CD ON (SHIST.change_by_badgeid = CD.badgeid)
        LEFT JOIN Schedule SCH using (sessionid)
        LEFT JOIN Rooms R using (roomid)
    WHERE
        DATEDIFF(NOW(), SHIST.change_ts) < 10
    ORDER BY SHIST.change_ts ASC;
EOD;

$report['xsl'] =<<<'EOD'
<?xml version="1.0" encoding="UTF-8" ?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
    <xsl:output encoding="UTF-8" indent="yes" method="html" />
    <xsl:include href="xsl/reportInclude.xsl" />
    <xsl:template match="/">
        <xsl:choose>
            <xsl:when test="doc/query[@queryName='change']/row">
                <table id="reportTable" class="table table-sm table-bordered">
                    <thead>
                        <tr class="table-primary">
                            <th>Date</th>
                            <th>Who</th>
                            <th>Track</th>
                            <th>Title</th>
                            <th>Status</th>
                            <th>Room Name</th>
                            <th>When</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        <xsl:apply-templates select="doc/query[@queryName='change']/row"/>
                    </tbody>
                </table>
            </xsl:when>
            <xsl:otherwise>
                <div class="text-info">No results found.</div>
            </xsl:otherwise>
        </xsl:choose>
    </xsl:template>

    <xsl:template match="doc/query[@queryName='change']/row">
        <tr>
            <td><xsl:value-of select="@change_ts_format" /></td>
            <td>
                <xsl:call-template name="showLinkedPubsname">
                    <xsl:with-param name="badgeid" select = "@change_by_badgeid" />
                    <xsl:with-param name="pubsname" select = "@pubsname" />
                    <xsl:with-param name="badgename" select = "@badgename" />
                    <xsl:with-param name="name" select = "@name" />
                </xsl:call-template>
            </td>
            <td><xsl:value-of select="@trackname" /></td>
            <td>
                <xsl:call-template name="showSessionTitle">
                    <xsl:with-param name="sessionid" select="@sessionid" />
                    <xsl:with-param name="title" select="@title" />
                </xsl:call-template>
            </td>
            <td><xsl:value-of select="@statusname" /></td>
            <td>
                <xsl:call-template name="showRoomName">
                    <xsl:with-param name="roomid" select = "@roomid" />
                    <xsl:with-param name="roomname" select = "@roomname" />
                </xsl:call-template>
            </td>
            <td><xsl:value-of select="@starttime" /></td>
            <td><xsl:value-of select="@description" /></td>
        </tr>
    </xsl:template>
</xsl:stylesheet>
EOD;
