<?php
// Copyright (c) 2018 Peter Olszowka. All rights reserved. See copyright document for more details.
$report = [];
$report['name'] = 'Interest v Schedule - sorted by pubsname';
$report['multi'] = 'true';
$report['output_filename'] = 'intvschedpanelnames.csv';
$report['description'] = 'Show who is interested in each panel and if they are assigned to it. Also show the scheduling information (sorted by pubsname)';
$report['categories'] = array(
    'Programming Reports' => 600,
);
$report['queries'] = [];
$report['queries']['participants'] =<<<'EOD'
SELECT
        X.pubsname,
        X.badgename,
        X.name,
        X.badgeid,
        X.trackname, 
        X.sessionid,
        X.title,
        X.rank,
        X.assigned,
        IF(moderator IS NULL OR moderator=0,0,1) AS moderator,
        X.willmoderate,
        Y.roomid,
        Y.roomname,
        DATE_FORMAT(ADDTIME('$ConStartDatim$',Y.starttime),'%a %l:%i %p') AS startTime
    FROM (
        SELECT
                PI.badgeid,
                PI.pubsname,
                PI.badgename, 
                PI.name,
                PI.sessionid,
                POS.sessionid AS assigned,
                moderator,
                willmoderate,
                title,
                trackname,
                `rank`
            FROM (
                        SELECT
                                T.trackname,
                                S.title,
                                S.sessionid,
                                P.badgeid,
                                P.pubsname,
                                CD.badgename, 
                                concat(CD.firstname,' ',CD.lastname) AS name,
                                PSI.willmoderate,
                                PSI.rank
                            FROM
                                     Sessions S
                                JOIN Tracks T USING(trackid)
                                JOIN ParticipantSessionInterest PSI USING(sessionid)
                                JOIN Participants P USING(badgeid)
                                JOIN CongoDump CD USING(badgeid)
                            WHERE
                                P.interested=1 
                                AND ((PSI.rank is not NULL
                                AND PSI.rank != 0) OR PSI.willmoderate = 1)
                                AND S.statusid in (select statusid from SessionStatuses where may_be_scheduled = 1)
                ) PI 
                LEFT JOIN ParticipantOnSession POS USING(badgeid, sessionid)
        ) AS X 
        LEFT JOIN (
                SELECT
                        SCH.starttime,
                        R.roomname,
                        R.roomid,
                        SCH.sessionid
                    FROM
                             Schedule SCH
                        JOIN Rooms R USING(roomid)
                 ) AS Y USING(sessionid)
    ORDER BY
        SUBSTRING_INDEX(pubsname,' ',-1), pubsname, `rank`;
EOD;
$report['xsl'] =<<<'EOD'
<?xml version="1.0" encoding="UTF-8" ?>
<xsl:stylesheet version="1.1" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
    <xsl:output encoding="UTF-8" indent="yes" method="html" />
    <xsl:include href="xsl/reportInclude.xsl" />
    <xsl:template match="/">
        <xsl:choose>
            <xsl:when test="doc/query[@queryName='participants']/row">
                <table id="reportTable" class="table table-sm table-bordered">
                    <thead>
                        <tr>
                            <th>Pubsname</th>
                            <th>Track Name</th>
                            <th>Session ID</th>
                            <th>Title</th>
                            <th>Rank</th>
                            <th>Will Moderate</th>
                            <th>Assigned ?</th>
                            <th>Moderator ?</th>
                            <th>Room Name</th>
                            <th>Start Time</th>
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

    <xsl:template match="doc/query[@queryName='participants']/row">
        <tr>
            <td>
                <xsl:call-template name="showLinkedPubsname">
                    <xsl:with-param name="badgeid" select = "@badgeid" />
                    <xsl:with-param name="pubsname" select = "@pubsname" />
                    <xsl:with-param name="badgename" select = "@badgename" />
                    <xsl:with-param name="name" select = "@name" />
                </xsl:call-template>            
            </td>
            <td><xsl:value-of select="@trackname" /></td>
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
            <td><xsl:value-of select="@rank" /></td>
            <td><xsl:if test="@willmoderate = '1'">Y</xsl:if></td>
            <td>
                <xsl:if test="@assigned">Yes</xsl:if>
            </td>
            <td>
                <xsl:if test="@moderator='1'">Yes</xsl:if>
            </td>
            <td>
                <xsl:call-template name="showRoomName">
                    <xsl:with-param name="roomid" select = "@roomid" />
                    <xsl:with-param name="roomname" select = "@roomname" />
                </xsl:call-template>
            </td>
            <td><xsl:value-of select="@startTime" /></td>
        </tr>
    </xsl:template>
</xsl:stylesheet>
EOD;
