<?php
$report = [];
$report['name'] = 'Participant List of Interests ';
$report['description'] = 'List all program participants who are attending and their interests.';
$report['categories'] = array(
    'Programming Reports' => 1,
);
$report['queries'] = [];
$report['queries']['participants'] =<<<'EOD'
SELECT
        CONCAT(CD.lastname,", ",CD.firstname) AS name,
        P.pubsname,
        CD.badgeid 
    FROM
             CongoDump CD
        JOIN Participants P USING (badgeid)
    WHERE
        P.interested = 1
    ORDER BY
        CD.lastname, CD.firstname
EOD;
$report['queries']['interests'] =<<<'EOD'
SELECT
        P.badgeid,
        I.interestname
    FROM
             Participants P
        JOIN ParticipantHasInterest PHI USING (badgeid)
        JOIN Interests I USING (interestid)
    WHERE
        P.interested = 1
    ORDER BY
        P.badgeid, I.display_order
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
                        <th class="report">Last name, first name</th>
                        <th class="report">Name for Publications</th>
                        <th class="report">Person ID</th>
                        <th class="report">Interests</th>
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
        <xsl:variable name="badgeid" select="@badgeid"/>
        <tr>
            <td class="report"><xsl:value-of select="@name"/></td>
            <td class="report"><xsl:value-of select="@pubsname"/></td>
            <td class="report"><xsl:call-template name="showBadgeid"><xsl:with-param name="badgeid" select="@badgeid"/></xsl:call-template></td>
            <td class="report">
                <xsl:for-each select="/doc/query[@queryName = 'interests']/row[@badgeid = $badgeid]">
                    <div><xsl:value-of select="@interestname"/></div>
                </xsl:for-each>
            </td>
        </tr>
    </xsl:template>
</xsl:stylesheet>
EOD;
