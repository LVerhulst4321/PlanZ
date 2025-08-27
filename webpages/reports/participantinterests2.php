<?php
$report = [];
$report['name'] = 'Participant List of Interests ';
$report['multi'] = 'true';
$report['output_filename'] = 'participantinterests2.csv';
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
                <table class="table table-sm table-bordered">
                    <tr>
                        <th>Last name, first name</th>
                        <th>Name for Publications</th>
                        <th>Person ID</th>
                        <th>Interests</th>
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
            <td><xsl:value-of select="@name"/></td>
            <td><xsl:value-of select="@pubsname"/></td>
            <td><xsl:call-template name="showBadgeid"><xsl:with-param name="badgeid" select="@badgeid"/></xsl:call-template></td>
            <td>
                <xsl:for-each select="/doc/query[@queryName = 'interests']/row[@badgeid = $badgeid]">
                    <div><xsl:value-of select="@interestname"/></div>
                </xsl:for-each>
            </td>
        </tr>
    </xsl:template>
</xsl:stylesheet>
EOD;
