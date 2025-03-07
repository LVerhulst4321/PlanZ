<?php
// Copyright (c) 2018 Peter Olszowka. All rights reserved. See copyright document for more details.
$report = [];
$report['name'] = 'Session Interest Report (all info, include unranked)';
$report['multi'] = 'true';
$report['output_filename'] = 'session_interests.csv';
$report['description'] = 'Shows who has expressed interest in each session, how they ranked it, what they said, if they will moderate... Large Report. (All data included including for invited sessions.)';
$report['categories'] = array(
    'Programming Reports' => 990,
);
$report['queries'] = [];
$report['queries']['sessions'] =<<<'EOD'
SELECT
        T.trackname,
        S.sessionid,
        S.title,
        P.pubsname,
        P.badgeid,
        P.allow_streaming,
        P.allow_recording,
        PSI.rank,
        PSI.willmoderate,
        PSI.attend_type,
        PSI.comments,
        CD.badgename,
        concat(CD.firstname,' ',CD.lastname) AS name
    FROM
        Sessions S
        JOIN Tracks T USING (trackid)
        JOIN Types TY USING (typeid)
        JOIN ParticipantSessionInterest PSI USING (sessionid)
        JOIN Participants P USING (badgeid)
        JOIN CongoDump CD USING(badgeid)
    WHERE
        P.interested = 1
        AND S.statusid IN (2, 3, 7) ## Vetted, Scheduled, Assigned
    ORDER BY
        T.trackname, S.title;
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
                            <th>Pubsname</th>
                            <th>Person ID</th>
                            <th>Rank</th>
                            <th>Will Mod</th>
                            <th>How Attend</th>
                            <th>Allow Streaming</th>
                            <th>Allow Recording</th>
                            <th>Comments</th>
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
                <xsl:call-template name="showLinkedPubsname">
                    <xsl:with-param name="badgeid" select = "@badgeid" />
                    <xsl:with-param name="pubsname" select = "@pubsname" />
                    <xsl:with-param name="badgename" select = "@badgename" />
                    <xsl:with-param name="name" select = "@name" />
                </xsl:call-template>
            </td>
            <td>
                <xsl:call-template name="showBadgeid">
                    <xsl:with-param name="badgeid" select = "@badgeid" />
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
