<?php
// Copyright (c) 2018-2019 Peter Olszowka. All rights reserved. See copyright document for more details.
$report = [];
$report['name'] = 'Name Report';
$report['multi'] = 'true';
$report['output_filename'] = 'name_to_pubsname_and_badgename.csv';
$report['description'] = 'Maps id, pubsname, badgename and first and last name together (includes every record in the database regardless of status).';
$report['categories'] = array(
    'Events Reports' => 670,
    'Programming Reports' => 1,
    'Participant Info Reports' => 670,
    'Registration Reports' => 1,
);
$report['columns'] = array(
    null,
    null,
    null,
    null,
    null,
    null,
    null,
    null,
    null
);
$report['queries'] = [];
$report['queries']['participants'] =<<<'EOD'
SELECT
        CD.badgeid,
        P.pubsname,
        P.sortedpubsname,
        CD.badgename,
        CD.lastname,
        CD.firstname,
        CD.badgeid,
        P.interested,
        P.anonymous,
        IF(EXISTS(
            SELECT SCH.scheduleid
                FROM
                         Schedule SCH
                    JOIN Sessions S USING (sessionid)
                    JOIN ParticipantOnSession POS USING (sessionid)
                WHERE
                        POS.badgeid = CD.badgeid
                    AND S.pubstatusid != 3 /* not Do Not Print */
            ), 1, 0) AS participantIsScheduled
    FROM
             CongoDump CD
        JOIN Participants P USING (badgeid)
    ORDER BY
        participantIsScheduled,
        P.interested,
        P.sortedpubsname;
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
                        <tr style="height:2.6rem">
                            <th>Person ID</th>
                            <th>Name for Publications</th>
                            <th>Last Name</th>
                            <th>First Name</th>
                            <th>Badge Name</th>
                            <th>Sorted Pubs Name</th>
                            <th>Interested</th>
                            <th>Anon</th>
                            <th>Scheduled</th>
                        </tr>
                    </thead>
                    <xsl:apply-templates select="doc/query[@queryName='participants']/row" />
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
            <td><xsl:value-of select="@pubsname" /></td>
            <td><xsl:value-of select="@lastname" /></td>
            <td><xsl:value-of select="@firstname" /></td>
            <td><xsl:value-of select="@badgename" /></td>
            <td><xsl:value-of select="@sortedpubsname" /></td>
            <td>
                <xsl:choose>
                    <xsl:when test="@interested='0'">Didn't respond</xsl:when>
                    <xsl:when test="@interested='1'">Yes</xsl:when>
                    <xsl:when test="@interested='2'">No</xsl:when>
                    <xsl:otherwise>Didn't log in</xsl:otherwise>
                </xsl:choose>
            </td>
            <td><xsl:if test="@anonymous = 'Y'">Yes</xsl:if></td>
            <td>
                <xsl:choose>
                    <xsl:when test="@participantIsScheduled='1'">Yes</xsl:when>
                    <xsl:otherwise>No</xsl:otherwise>
                </xsl:choose>
            </td>
        </tr>
    </xsl:template>
</xsl:stylesheet>
EOD;
