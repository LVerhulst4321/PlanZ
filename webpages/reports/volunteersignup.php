<?php
// Created by BC Holmes on 7 Apr 2022
$report = [];
$report['name'] = 'Volunteer Shift Sign Up';
$report['multi'] = 'true';
$report['output_filename'] = 'volunteer_shifts.csv';
$report['module'] = 'planz.volunteering';
$CON_NAME = CON_NAME;
$report['description'] = 'Participants who have signed up for volunteer shifts';
$report['categories'] = array(
    'Participant Info Reports' => 1145,
);
$report['queries'] = [];
$report['queries']['shifts'] =<<<'EOD'
SELECT
        CD.badgeid,
        CONCAT(CD.firstname, CD.lastname) AS name,
        CONCAT(CD.lastname, CD.firstname) AS nameSort,
        CD.badgename,
        P.pubsname,
        DATE_FORMAT(CONVERT_TZ(VS.from_time, 'UTC', '$DB_DEFAULT_TIMEZONE$'), '%a %h:%i%p') AS from_time,
        DATE_FORMAT(CONVERT_TZ(VS.to_time, 'UTC', '$DB_DEFAULT_TIMEZONE$'), '%a %h:%i%p') AS to_time,
        VS.location,
        VS.max_volunteer_count,
        VS.min_volunteer_count,
        VJ.job_name,
        VJ.is_online

    FROM      volunteer_shift VS
         JOIN volunteer_job VJ ON (VS.volunteer_job_id = VJ.id)
    LEFT JOIN participant_has_volunteer_shift PHVS ON (PHVS.volunteer_shift_id = VS.id)
    LEFT JOIN CongoDump CD ON (PHVS.badgeid = CD.badgeid)
    LEFT JOIN Participants P ON (PHVS.badgeid = P.badgeid)
    WHERE VS.con_id in (select id from current_con);
EOD;
$report['xsl'] =<<<'EOD'
<?xml version="1.0" encoding="UTF-8" ?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
    <xsl:output encoding="UTF-8" indent="yes" method="xml" />
    <xsl:include href="xsl/reportInclude.xsl" />
    <xsl:template match="/">
        <xsl:choose>
            <xsl:when test="doc/query[@queryName='shifts']/row">
                <table id="reportTable" class="table table-sm table-bordered">
                    <thead>
                        <tr style="height:2.6rem">
                            <th>Job</th>
                            <th>Shift</th>
                            <th>Person</th>
                            <th>Needs</th>
                            <th>Online?</th>
                            <th>Location</th>
                        </tr>
                    </thead>
                    <tbody>
                        <xsl:apply-templates select="/doc/query[@queryName='shifts']/row"/>
                    </tbody>
                </table>
            </xsl:when>
            <xsl:otherwise>
                <div class="alert alert-danger">No results found.</div>
            </xsl:otherwise>
        </xsl:choose>
    </xsl:template>
    <xsl:template match="/doc/query[@queryName='shifts']/row">
        <tr>
            <td><xsl:value-of select="@job_name"/></td>
            <td><xsl:value-of select="@from_time"/> - <xsl:value-of select="@to_time"/></td>
            <td>
                <xsl:if test="@badgeid != ''">
                    <xsl:call-template name="showLinkedPubsname">
                        <xsl:with-param name="badgeid" select = "@badgeid" />
                        <xsl:with-param name="pubsname" select = "@pubsname" />
                        <xsl:with-param name="badgename" select = "@badgename" />
                        <xsl:with-param name="name" select = "@name" />
                    </xsl:call-template>
                </xsl:if>
            </td>
            <td><xsl:value-of select="@min_volunteer_count"/> to <xsl:value-of select="@max_volunteer_count"/></td>
            <td>
                <xsl:choose>
                    <xsl:when test="@is_online = 1">
                        Yes
                    </xsl:when>
                    <xsl:otherwise>
                        No
                    </xsl:otherwise>
                </xsl:choose>
            </td>
            <td><xsl:value-of select="@location"/></td>
        </tr>
    </xsl:template>
</xsl:stylesheet>
EOD;
