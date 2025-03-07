<?php
// Copyright (c) 2018 Peter Olszowka. All rights reserved. See copyright document for more details.
$report = [];
$report['name'] = 'Session Interest by participant (all info)';
$report['multi'] = 'true';
$report['output_filename'] = 'session_interest_by_part.csv';
$report['description'] = 'Shows who has expressed interest in each session, how they ranked it, what they said, if they will moderate... Large Report. (All data included including for invited sessions.) order by participant';
$report['categories'] = array(
    'Programming Reports' => 980,
);
$report['queries'] = [];
$report['queries']['participants'] =<<<'EOD'
SELECT
        P.badgeid,
        P.pubsname,
        T.trackname,
        S.sessionid,
        S.title,
        P.allow_streaming,
        P.allow_recording,
        DATE_FORMAT(ADDTIME('$ConStartDatim$',SCH.starttime),'%a %l:%i %p') AS starttime,
        DATE_FORMAT(S.duration,'%i') AS durationmin,
        DATE_FORMAT(S.duration,'%k') AS durationhrs,
        PSI.rank,
        PSI.willmoderate,
        PSI.attend_type,
        PSI.comments
    FROM
                  Participants P
             JOIN CongoDump CD USING (badgeid)
             JOIN ParticipantSessionInterest PSI USING (badgeid)
             JOIN Sessions S USING (sessionid)
             JOIN Tracks T USING (trackid)
             JOIN Types TY USING (typeid)
        LEFT JOIN Schedule SCH USING (sessionid)
    WHERE
            P.interested = 1
        AND T.selfselect = 1
        AND TY.selfselect = 1
        AND S.invitedguest = 0
        AND S.statusid IN (1, 2, 3, 6, 7) ## Brainstorm, Vetted, Scheduled, Edit Me, Assigned
    ORDER BY
        IF(INSTR(P.pubsname, CD.lastname) > 0, CD.lastname, SUBSTRING_INDEX(P.pubsname, ' ', -1)),
        CD.firstname,
        T.trackname;
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
                    <col style="width:5%" />
                    <col style="width:5%" />
                    <col style="width:5%" />
                    <col style="width:5%" />
                    <col style="width:10%" />
                    <col style="width:5%" />
                    <col style="width:5%" />
                    <col style="width:5%" />
                    <col style="width:5%" />
                    <col style="width:5%" />
                    <col style="width:5%" />
                    <col style="width:5%" />
                    <col style="width:35%" />
                    <thead>
                        <tr>
                            <th>Person ID</th>
                            <th>Pubsname</th>
                            <th>Track</th>
                            <th>Session ID</th>
                            <th>Title</th>
                            <th>Start Time</th>
                            <th>Duration</th>
                            <th>Rank</th>
                            <th>Moderator</th>
                            <th>How Attend</th>
                            <th>Allow Streaming</th>
                            <th>Allow Recording</th>
                            <th>Comments</th>
                        </tr>
                    </thead>
                    <tbody>
                        <xsl:apply-templates select="doc/query[@queryName='participants']/row"/>
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
                <xsl:call-template name="showBadgeid">
                    <xsl:with-param name="badgeid" select = "@badgeid" />
                </xsl:call-template>
            </td>
            <td>
                <xsl:call-template name="showPubsname">
                    <xsl:with-param name="badgeid" select = "@badgeid" />
                    <xsl:with-param name="pubsname" select = "@pubsname" />
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
            <td><xsl:value-of select="@starttime" /></td>
            <td>
                <xsl:call-template name="showDuration">
                    <xsl:with-param name="durationhrs" select = "@durationhrs" />
                    <xsl:with-param name="durationmin" select = "@durationmin" />
                </xsl:call-template>
            </td>
            <td><xsl:value-of select="@rank" /></td>
            <td>
                <xsl:if test="@willmoderate='1'">Yes</xsl:if>
            </td>
            <td>
                <xsl:choose>
                    <xsl:when test="@attend_type = '1'">
                        In Person
                    </xsl:when>
                    <xsl:when test="@attend_type = '2'">
                        Online
                    </xsl:when>
                    <xsl:when test="@attend_type = '3'">
                        Either
                    </xsl:when>
                    <xsl:otherwise>
                    </xsl:otherwise>
                </xsl:choose>
            </td>
            <td class="report">
                <xsl:choose>
                    <xsl:when test="@allow_streaming='1'">Yes</xsl:when>
                    <xsl:when test="@allow_streaming='2'">No</xsl:when>
                    <xsl:otherwise>Didn't respond</xsl:otherwise>
                </xsl:choose>
            </td>
            <td class="report">
                <xsl:choose>
                    <xsl:when test="@allow_recording='1'">Yes</xsl:when>
                    <xsl:when test="@allow_recording='2'">No</xsl:when>
                    <xsl:otherwise>Didn't respond</xsl:otherwise>
                </xsl:choose>
            </td>
            <td><xsl:value-of select="@comments" /></td>
        </tr>
    </xsl:template>
</xsl:stylesheet>
EOD;
