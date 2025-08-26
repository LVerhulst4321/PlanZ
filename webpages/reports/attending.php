<?php
// Copyright (c) 2018-2019 Peter Olszowka. All rights reserved. See copyright document for more details.
$report = [];
$report['name'] = 'Attending Query (all info)';
$report['multi'] = 'true';
$report['output_filename'] = 'attending.csv';
$report['description'] = 'Shows who (of program participants only) has responded and if they are attending.';
$report['categories'] = array(
    'Participant Info Reports' => 300,
);
$report['columns'] = array(
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
        CD.firstname,
        CD.lastname,
        P.pubsname,
        P.badgeid,
        P.interested,
        P.share_email,
        P.use_photo,
        P.allow_streaming,
        P.allow_recording
    FROM
             Participants P
        JOIN CongoDump CD USING (badgeid)
        JOIN UserHasPermissionRole UHPR USING (badgeid)
    WHERE
        UHPR.permroleid = 3 ## Program Participant
    ORDER BY
        P.pubsname;
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
                            <th>Registration Name</th>
                            <th>Pubs Name</th>
                            <th>Person ID</th>
                            <th><xsl:text disable-output-escaping="yes">Interested &amp;amp; Attending</xsl:text></th>
                            <th>May share email</th>
                            <th>May use photo</th>
                            <th>Allow streaming</th>
                            <th>Allow recording</th>
                        </tr>
                    </thead>
                    <xsl:apply-templates select="/doc/query[@queryName='participants']/row"/>
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
            <td><xsl:value-of select="@firstname"/><xsl:text disable-output-escaping="yes">&amp;nbsp;</xsl:text><xsl:value-of select="@lastname"/></td>
            <td><xsl:value-of select="@pubsname"/></td>
            <td><xsl:call-template name="showBadgeid"><xsl:with-param name="badgeid" select="@badgeid"/></xsl:call-template></td>
            <td>
                <xsl:choose>
                    <xsl:when test="@interested='0'">Didn't respond</xsl:when>
                    <xsl:when test="@interested='1'">Yes</xsl:when>
                    <xsl:when test="@interested='2'">No</xsl:when>
                    <xsl:otherwise>Didn't log in</xsl:otherwise>
                </xsl:choose>
            </td>
            <td>
                <xsl:choose>
                    <xsl:when test="@share_email='1'">Yes</xsl:when>
                    <xsl:when test="@share_email='2'">No</xsl:when>
                    <xsl:otherwise>Didn't respond</xsl:otherwise>
                </xsl:choose>
            </td>
            <td>
                <xsl:choose>
                    <xsl:when test="@use_photo='1'">Yes</xsl:when>
                    <xsl:when test="@use_photo='2'">No</xsl:when>
                    <xsl:otherwise>Didn't respond</xsl:otherwise>
                </xsl:choose>
            </td>
            <td>
                <xsl:choose>
                    <xsl:when test="@allow_streaming='1'">Yes</xsl:when>
                    <xsl:when test="@allow_streaming='2'">No</xsl:when>
                    <xsl:otherwise>Didn't respond</xsl:otherwise>
                </xsl:choose>
            </td>
            <td>
                <xsl:choose>
                    <xsl:when test="@allow_recording='1'">Yes</xsl:when>
                    <xsl:when test="@allow_recording='2'">No</xsl:when>
                    <xsl:otherwise>Didn't respond</xsl:otherwise>
                </xsl:choose>
            </td>
        </tr>
    </xsl:template>
</xsl:stylesheet>
EOD;
