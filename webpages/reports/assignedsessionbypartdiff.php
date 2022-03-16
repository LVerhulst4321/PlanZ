<?php
$report = [];
$report['name'] = 'Differential Assigned Session by Participant';
$report['description'] = 'Recent changes (last 20 days) of whom has been assigned to each session ordered by created time.';
$report['categories'] = array(
    'Events Reports' => 1,
    'Programming Reports' => 1,
    'GOH Reports' => 1,
    'Publication Reports' => 1,
    'Program Ops Reports' => 1,
);
$report['queries'] = [];
$report['queries']['edits'] =<<<'EOD'
SELECT
        P.badgeid,
        CD.badgenumber,
        P.pubsname,
        S.sessionid,
        S.title,
        POS.moderator,
        POSH.createdts as ts,
        POSH.inactivatedts as its,
        R.roomname,
        DATE_FORMAT(ADDTIME('$ConStartDatim$',SCH.starttime),'%a %l:%i %p') AS starttime
    FROM
                        ParticipantOnSessionHistory POSH
        LEFT OUTER JOIN ParticipantOnSession POS ON (POS.badgeid=POSH.badgeid and POS.sessionid=POSH.sessionid)
                   JOIN Participants P ON (POSH.badgeid=P.badgeid)
                   JOIN CongoDump CD ON (POSH.badgeid=CD.badgeid)
                   JOIN Sessions S ON (POSH.sessionid=S.sessionid)
              LEFT JOIN Schedule SCH ON (S.sessionid=SCH.sessionid)
              LEFT JOIN Rooms R USING (roomid)
    WHERE
        DATE_SUB(NOW(), INTERVAL 20 DAY) < POSH.createdts
        OR DATE_SUB(NOW(), INTERVAL 20 DAY) < POSH.inactivatedts
    ORDER BY
        POSH.createdts DESC
EOD;
$report['xsl'] =<<<'EOD'
<?xml version="1.0" encoding="UTF-8" ?>
<xsl:stylesheet version="1.1" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
    <xsl:output encoding="UTF-8" indent="yes" method="html" />
    <xsl:include href="xsl/reportInclude.xsl" />
    <xsl:template match="/">
        <xsl:choose>
            <xsl:when test="doc/query[@queryName='edits']/row">
                <table class="report">
                    <tr>
                        <th class="report" style="white-space:nowrap;">Date &#038; Time Created</th>
                        <th class="report" style="white-space:nowrap;">Date &#038; Time Inactivated</th>
                        <th class="report" style="white-space:nowrap;">Person ID</th>
                        <th class="report">Badge Number</th>
                        <th class="report">Name for Publications</th>
                        <th class="report">Moderator</th>
                        <th class="report">Session ID</th>
                        <th class="report">Title</th>
                        <th class="report">Room</th>
                        <th class="report">Schedule Day &#038; Time</th>
                    </tr>
                    <xsl:apply-templates select="doc/query[@queryName='edits']/row" />
                </table>
            </xsl:when>
            <xsl:otherwise>
                <div class="alert alert-danger">No results found.</div>
            </xsl:otherwise>
        </xsl:choose>
    </xsl:template>

    <xsl:template match="doc/query[@queryName='edits']/row">
        <tr>
            <td class="report"><xsl:value-of select="@ts"/></td>
            <td class="report"><xsl:value-of select="@its"/></td>
            <td class="report">
                <xsl:call-template name="showBadgeid">
                    <xsl:with-param name="badgeid" select = "@badgeid" />
                </xsl:call-template>
            </td>
            <td class="report"><xsl:value-of select="@badgenumber"/></td>
            <td class="report" style="white-space:nowrap;"><xsl:value-of select="@pubsname"/></td>
            <td class="report">
                <xsl:if test="@moderator = '1'">
                    <xsl:text>MOD</xsl:text>
                </xsl:if>
            </td>
            <td class="report">
                <xsl:call-template name="showSessionid">
                    <xsl:with-param name="sessionid" select = "@sessionid" />
                </xsl:call-template>
            </td>
            <td class="report">
                <xsl:call-template name="showSessionTitle">
                    <xsl:with-param name="sessionid" select = "@sessionid" />
                    <xsl:with-param name="title" select = "@title" />
                </xsl:call-template>
            </td>
            <td class="report">
                <xsl:call-template name="showRoomName">
                    <xsl:with-param name="roomid" select = "@roomid" />
                    <xsl:with-param name="roomname" select = "@roomname" />
                </xsl:call-template>
            </td>
            <td class="report"><xsl:value-of select="@starttime"/></td>
        </tr>
    </xsl:template>
</xsl:stylesheet>
EOD;
