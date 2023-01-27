<?php
$report = [];
$report['name'] = 'Differential Assigned Session by Participant';
$report['multi'] = 'true';
$report['output_filename'] = 'assigned_session_by_participant_differential.csv';
$report['description'] = 'Recent changes (last 20 days) of whom has been assigned to each session.';
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
        POSH.change_ts as ts,
        POSH.change_type,
        R.roomname,
        DATE_FORMAT(ADDTIME('$ConStartDatim$',SCH.starttime),'%a %l:%i %p') AS starttime
    FROM
                        participant_on_session_history POSH
        LEFT OUTER JOIN ParticipantOnSession POS ON (POS.badgeid=POSH.badgeid and POS.sessionid=POSH.sessionid)
                   JOIN Participants P ON (POSH.badgeid=P.badgeid)
                   JOIN CongoDump CD ON (POSH.badgeid=CD.badgeid)
                   JOIN Sessions S ON (POSH.sessionid=S.sessionid)
              LEFT JOIN Schedule SCH ON (S.sessionid=SCH.sessionid)
              LEFT JOIN Rooms R USING (roomid)
    WHERE
        DATE_SUB(NOW(), INTERVAL 20 DAY) < POSH.change_ts
    ORDER BY
        POSH.change_ts DESC
EOD;
$report['xsl'] =<<<'EOD'
<?xml version="1.0" encoding="UTF-8" ?>
<xsl:stylesheet version="1.1" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
    <xsl:output encoding="UTF-8" indent="yes" method="html" />
    <xsl:include href="xsl/reportInclude.xsl" />
    <xsl:template match="/">
        <xsl:choose>
            <xsl:when test="doc/query[@queryName='edits']/row">
                <table id="reportTable" class="table table-sm table-bordered">
                    <thead>
                        <tr>
                            <th style="white-space:nowrap;">Date &#038; Time Changed</th>
                            <th style="white-space:nowrap;">Change Type</th>
                            <th style="white-space:nowrap;">Person ID</th>
                            <th>Badge Number</th>
                            <th>Name for Publications</th>
                            <th>Moderator</th>
                            <th>Session ID</th>
                            <th>Title</th>
                            <th>Room</th>
                            <th>Schedule Day &#038; Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        <xsl:apply-templates select="doc/query[@queryName='edits']/row" />
                    </tbody>
                </table>
            </xsl:when>
            <xsl:otherwise>
                <div class="alert alert-danger">No results found.</div>
            </xsl:otherwise>
        </xsl:choose>
    </xsl:template>

    <xsl:template match="doc/query[@queryName='edits']/row">
        <tr>
            <td><xsl:value-of select="@ts"/></td>
            <td>
                <xsl:choose>
                    <xsl:when test="@change_type = 'insert_assignment'">
                        <xsl:text>Added</xsl:text>
                    </xsl:when>
                    <xsl:when test="@change_type = 'remove_assignment'">
                        <xsl:text>Removed</xsl:text>
                    </xsl:when>
                    <xsl:when test="@change_type = 'assign_moderator'">
                        <xsl:text>Made moderator</xsl:text>
                    </xsl:when>
                    <xsl:when test="@change_type = 'remove_moderator'">
                        <xsl:text>Remove moderator</xsl:text>
                    </xsl:when>
                    <xsl:otherwise>
                        <xsl:value-of select="@change_type"/>
                    </xsl:otherwise>
                </xsl:choose>
            </td>
            <td>
                <xsl:call-template name="showBadgeid">
                    <xsl:with-param name="badgeid" select = "@badgeid" />
                </xsl:call-template>
            </td>
            <td><xsl:value-of select="@badgenumber"/></td>
            <td style="white-space:nowrap;"><xsl:value-of select="@pubsname"/></td>
            <td>
                <xsl:if test="@moderator = '1'">
                    <xsl:text>MOD</xsl:text>
                </xsl:if>
            </td>
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
            <td>
                <xsl:call-template name="showRoomName">
                    <xsl:with-param name="roomid" select = "@roomid" />
                    <xsl:with-param name="roomname" select = "@roomname" />
                </xsl:call-template>
            </td>
            <td><xsl:value-of select="@starttime"/></td>
        </tr>
    </xsl:template>
</xsl:stylesheet>
EOD;
