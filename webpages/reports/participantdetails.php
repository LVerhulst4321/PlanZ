<?php
$report = [];
$report['name'] = 'Participant Details';
$report['description'] = 'Details about the panelists such as race and accessibility needs.';
$report['categories'] = array(
    'Programming Reports' => 1,
);
$report['queries'] = [];
$report['queries']['participants'] =<<<'EOD'
SELECT
        P.badgeid,
        P.pubsname,
        P.website,
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
        IF(instr(P.pubsname, CD.lastname) > 0, CD.lastname, substring_index(P.pubsname, ' ', -1)), CD.firstname;
EOD;
$report['xsl'] =<<<'EOD'
<?xml version="1.0" encoding="UTF-8" ?>
<xsl:stylesheet version="1.1" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
    <xsl:output encoding="UTF-8" indent="yes" method="html" />
    <xsl:include href="xsl/reportInclude.xsl" />
    <xsl:template match="/">
        <xsl:choose>
            <xsl:when test="doc/query[@queryName='participants']/row">
                <table class="report">
                    <tr>
                        <th class="report" style="white-space: nowrap;">Person ID</th>
                        <th class="report" style="white-space: nowrap;">Name for Publications</th>
                        <th class="report">Race/Ethnicity</th>
                        <th class="report">Gender</th>
                        <th class="report">Sexual Orientation</th>
                        <th class="report">Day Job</th>
                        <th class="report">Are Range</th>
                        <th class="report">Accessibility Issues</th>
                        <th class="report">Website</th>
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
        <xsl:variable name="bagdeid" select="@badgeid" />
        <tr>
            <td class="report" style="white-space: nowrap;">
                <xsl:call-template name="showBadgeid">
                    <xsl:with-param name="badgeid" select = "@badgeid" />
                </xsl:call-template>
            </td>
            <td class="report" style="white-space: nowrap;"><xsl:value-of select="@pubsname"/></td>
            <td class="report"><xsl:value-of select="@ethnicity"/></td>
            <td class="report"><xsl:value-of select="@gender"/></td>
            <td class="report"><xsl:value-of select="@sexualorientation"/></td>
            <td class="report"><xsl:value-of select="@dayjob"/></td>
            <td class="report"><xsl:value-of select="@agerangename"/></td>
            <td class="report"><xsl:value-of select="@accessibilityissues"/></td>
            <td class="report"><xsl:value-of select="@website"/></td>
        </tr>
    </xsl:template>
</xsl:stylesheet>
EOD;
