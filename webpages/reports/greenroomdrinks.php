<?php
// Copyright (c) 2018 Peter Olszowka. All rights reserved. See copyright document for more details.
$report = [];
$report['name'] = 'Green room drinks';
$report['multi'] = 'true';
$report['output_filename'] = 'greenroomdrinks.csv';
$report['description'] = 'Lists all Sessions Scheduled in all Rooms with one participant per row.';
$report['categories'] = array(
    'Program Ops Reports' => 1,
);
$report['queries'] = [];
$report['queries']['schedule'] =<<<'EOD'
SELECT
        DATE_FORMAT(ADDTIME('$ConStartDatim$',SCH.starttime),'%a %l:%i %p') AS starttime,
        DATE_FORMAT(S.duration,'%i') as durationmin,
        DATE_FORMAT(S.duration,'%k') as durationhrs,
        R.roomid,
        R.roomname,
        S.sessionid,
        S.title,
        PS.pubstatusname,
        P.pubsname,
        P.badgeid,
        POS.moderator
    FROM
             Sessions S
        JOIN Schedule SCH USING (sessionid)
        JOIN Rooms R USING (roomid)
        JOIN PubStatuses PS USING (pubstatusid)
        JOIN ParticipantOnSession POS USING (sessionid)
        JOIN Participants P USING (badgeid)
        JOIN CongoDump CD USING (badgeid)
    ORDER BY
        SCH.starttime, R.roomname, IF(instr(P.pubsname,CD.lastname)>0,CD.lastname,substring_index(P.pubsname,' ',-1)), CD.firstname;
EOD;
$report['xsl'] =<<<'EOD'
<?xml version="1.0" encoding="UTF-8" ?>
<xsl:stylesheet version="1.1" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
    <xsl:output encoding="UTF-8" indent="yes" method="html" />
    <xsl:include href="xsl/reportInclude.xsl" />
    <xsl:template match="/">
        <xsl:choose>
            <xsl:when test="doc/query[@queryName='schedule']/row">
                <table class="table table-sm table-bordered">
                   <col style="width:7em" />
                    <col style="width:6em" />
                    <col style="width:12em" />
                    <col />
                    <col style="width:6.2em" />
                    <col style="width:25em" />
                    <tr>
                        <th>Start Time</th>
                        <th>Duration</th>
                        <th>Room Name</th>
                        <th>Title</th>
                        <th>Pub. Status</th>
                        <th>Participant</th>
                    </tr>
                    <xsl:apply-templates select="/doc/query[@queryName='schedule']/row"/>
                </table>
            </xsl:when>
            <xsl:otherwise>
                <div class="alert alert-danger">No results found.</div>
            </xsl:otherwise>
        </xsl:choose>
    </xsl:template>
    <xsl:template match="/doc/query[@queryName='schedule']/row">
        <xsl:variable name="sessionid" select="@sessionid" />
        <tr>
            <xsl:choose>
                <xsl:when test="preceding-sibling::row[1]/@sessionid = @sessionid">
                    <td />
                    <td />
                    <td />
                    <td />
                    <td />
                </xsl:when>
                <xsl:otherwise>
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
                    <td>
                        <xsl:call-template name="showSessionTitle">
                            <xsl:with-param name="sessionid" select = "@sessionid" />
                            <xsl:with-param name="title" select = "@title" />
                        </xsl:call-template>
                    </td>
                    <td><xsl:value-of select="@pubstatusname" /></td>
                </xsl:otherwise>
            </xsl:choose>
            <td>
                <xsl:call-template name="showPubsnameWithBadgeid">
                    <xsl:with-param name="badgeid" select = "@badgeid" />
                    <xsl:with-param name="pubsname" select = "@pubsname" />
                </xsl:call-template>
                <xsl:if test="@moderator='1'">
                    (MOD)
                </xsl:if>
            </td>
        </tr>
    </xsl:template>
</xsl:stylesheet>
EOD;
