<?php
// Copyright (c) 2018-2019 Peter Olszowka. All rights reserved. See copyright document for more details.
$report = [];
$report['multi'] = 'true';
$report['output_filename'] = 'participantnotsignedup.csv';
$report['name'] = 'Participants signed up for sessions not coming';
$report['description'] = 'The list of all participants who have entered interest in a session, but are currently not flagged as intending to attend.';
$report['categories'] = array(
    'Participant Info Reports' => 120,
);
$report['columns'] = array(
    null,
    null,
    null,
    null,
    array("orderable" => false)
);
$report['queries'] = [];
$report['queries']['participants'] =<<<'EOD'
SELECT
        CD.badgeid,
        CD.firstname,
        CD.lastname,
        CD.badgename,
        P.pubsname
    FROM
             Participants P
        JOIN CongoDump CD USING (badgeid)
    WHERE
            NOT (P.interested <=> 1)
        AND EXISTS (
            SELECT *
                FROM
                    ParticipantSessionInterest PSI
                WHERE
                    PSI.badgeid = P.badgeid
            )
        AND EXISTS (
            SELECT *
                FROM
                    UserHasPermissionRole UHPR
                WHERE
                    UHPR.badgeid = P.badgeid
                    AND UHPR.permroleid IN (3,6,9,10,11) /* Participants roles that can log in: Participant, Event Organizer, LARP Part., LARP Org., Tabletop game Part. */
            );
EOD;
$report['queries']['sessions'] =<<<'EOD'
SELECT
        PSI.badgeid,
        PSI.sessionid,
        S.title
    FROM
             ParticipantSessionInterest PSI
        JOIN Sessions S USING (sessionid)
        JOIN Participants P USING (badgeid)
    WHERE
            NOT (P.interested <=> 1)
        AND EXISTS (
            SELECT *
                FROM
                    UserHasPermissionRole UHPR
                WHERE
                    UHPR.badgeid = P.badgeid
                    AND UHPR.permroleid IN (3,6,9,10,11) /* Participants roles that can log in: Participant, Event Organizer, LARP Part., LARP Org., Tabletop game Part. */
            );
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
                            <th style="height:2.6rem">Person Id</th>
                            <th>Pubs Name</th>
                            <th>Badge Name</th>
                            <th>Last Name, First Name</th>
                            <th>Interested in Sessions</th>
                        </tr>
                    </thead>
                    <xsl:apply-templates select="/doc/query[@queryName='participants']/row" />
                </table>
            </xsl:when>
            <xsl:otherwise>
                <div class="alert alert-danger">No results found.</div>
            </xsl:otherwise>
        </xsl:choose>
    </xsl:template>

    <xsl:template match="/doc/query[@queryName='participants']/row">
        <xsl:variable name="badgeid" select="@badgeid" />
        <tr>
            <td><xsl:call-template name="showBadgeid"><xsl:with-param name="badgeid" select="@badgeid"/></xsl:call-template></td>
            <td><xsl:value-of select="@pubsname"/></td>
            <td><xsl:value-of select="@badgename"/></td>
            <td><xsl:value-of select="@lastname"/><xsl:text disable-output-escaping="yes">,&amp;nbsp;</xsl:text><xsl:value-of select="@firstname"/></td>
            <td>
                <xsl:apply-templates select="/doc/query[@queryName='sessions']/row[@badgeid = $badgeid]" />
            </td>
        </tr>
    </xsl:template>

    <xsl:template match="/doc/query[@queryName='sessions']/row">
        <div>
            <xsl:call-template name="showSessionid"><xsl:with-param name="sessionid" select="@sessionid"/></xsl:call-template>
            <xsl:text disable-output-escaping="yes">&amp;nbsp;</xsl:text> 
            <xsl:call-template name="showSessionTitle">
                <xsl:with-param name="sessionid" select="@sessionid"/>
                <xsl:with-param name="title" select="@title"/>
            </xsl:call-template>
        </div>
    </xsl:template>
</xsl:stylesheet>
EOD;
