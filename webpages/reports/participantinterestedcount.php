<?php
// Copyright (c) 2018 Peter Olszowka. All rights reserved. See copyright document for more details.
$report = [];
$report['multi'] = 'true';
$report['output_filename'] = 'participantinterestedcount.csv';
$report['name'] = 'Participant Interested Count';
$report['description'] = 'Quick count of participants that are interested in attending.';
$report['categories'] = array(
    'Participant Info Reports' => 710,
    'Programming Reports' => 1,
);
$report['queries'] = [];
$report['queries']['participants'] =<<<'EOD'
SELECT
        P.interested,
        count(*) AS interestedCount,
        CASE WHEN CD.regtype = "" THEN "Unregistered" ELSE "Registered" END AS regstatus
    FROM
             Participants P
        JOIN UserHasPermissionRole UHPR USING (badgeid)
        JOIN CongoDump CD using (badgeid)
    WHERE
        UHPR.permroleid = 3 /* Program Participant */
    GROUP BY
        P.interested, regstatus
    ORDER BY
        P.interested, regstatus DESC
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
                    <tr>
                        <th>Interested Status</th>
                        <th>Reg Status</th>
                        <th>Count</th>
                    </tr>
                    <xsl:apply-templates select="doc/query[@queryName='participants']/row"/>
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
                <xsl:choose>
                    <xsl:when test="@interested='0'">Didn't respond</xsl:when>
                    <xsl:when test="@interested='1'">Yes</xsl:when>
                    <xsl:when test="@interested='2'">No</xsl:when>
                    <xsl:otherwise>Didn't log in</xsl:otherwise>
                </xsl:choose>
            </td>
            <td><xsl:value-of select="@regstatus"/></td>
            <td class="text-right"><xsl:value-of select="@interestedCount"/></td>
        </tr>
    </xsl:template>
</xsl:stylesheet>
EOD;
