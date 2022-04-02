<?php
$report = [];
$report['name'] = 'Participant Details';
$report['multi'] = 'true';
$report['output_filename'] = 'category_sess_count_2.csv';
$report['description'] = 'Details about the panelists such as race and accessibility needs.';
$report['categories'] = array(
    'Programming Reports' => 1,
);
$report['queries'] = [];
$report['queries']['participants'] =<<<'EOD'
SELECT
        P.badgeid,
        P.pubsname,
        PD.dayjob,
        PD.accessibilityissues,
        PD.ethnicity,
        PD.gender,
        PD.sexualorientation,
        AR.agerangename
    FROM
             Participants P
        JOIN ParticipantDetails PD USING (badgeid)
        JOIN CongoDump CD USING (badgeid)
        JOIN AgeRanges AR USING (agerangeid)
    WHERE
        P.interested = 1
    ORDER BY
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
                        <tr>
                            <th style="white-space: nowrap;">Person ID</th>
                            <th style="white-space: nowrap;">Name for Publications</th>
                            <th>Race/Ethnicity</th>
                            <th>Gender</th>
                            <th>Sexual Orientation</th>
                            <th>Day Job</th>
                            <th>Age Range</th>
                            <th>Accessibility Issues</th>
                            <th>Website</th>
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
        <xsl:variable name="bagdeid" select="@badgeid" />
        <tr>
            <td style="white-space: nowrap;">
                <xsl:call-template name="showBadgeid">
                    <xsl:with-param name="badgeid" select = "@badgeid" />
                </xsl:call-template>
            </td>
            <td style="white-space: nowrap;"><xsl:value-of select="@pubsname"/></td>
            <td><xsl:value-of select="@ethnicity"/></td>
            <td><xsl:value-of select="@gender"/></td>
            <td><xsl:value-of select="@sexualorientation"/></td>
            <td><xsl:value-of select="@dayjob"/></td>
            <td><xsl:value-of select="@agerangename"/></td>
            <td><xsl:value-of select="@accessibilityissues"/></td>
            <td><xsl:value-of select="@website"/></td>
        </tr>
    </xsl:template>
</xsl:stylesheet>
EOD;
