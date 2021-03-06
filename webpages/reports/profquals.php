<?php
// Copyright (c) 2018-2019 Peter Olszowka. All rights reserved. See copyright document for more details.
$report = [];
$report['name'] = 'Participant Professional Qualifications ';
$report['description'] = 'List all program participants who are attending and their professional qualifications: (Lastname, firstname), Pubsname, Badgename, ID, Qualifications.';
$report['categories'] = array(
    'Participant Info Reports' => 800,
    'Programming Reports' => 1,
);
$report['columns'] = array(
    array("orderData" => 1),
    array("visible" => false),
    array("orderData" => 3),
    array("visible" => false),
    null,
    null,
    array("orderable" => false)
);
$report['queries'] = [];
$report['queries']['participants'] =<<<'EOD'
SELECT
        CONCAT(CD.firstname, " ", CD.lastname) AS name,
        CONCAT(CD.lastname, CD.firstname) AS nameSort,
        P.pubsname,
        CD.email,
        CD.badgeid,
        IF(INSTR(P.pubsname,CD.lastname) > 0, CD.lastname, SUBSTRING_INDEX(P.pubsname, ' ', -1)) AS pubsnameSort
    FROM
             CongoDump CD
        JOIN Participants P USING (badgeid)
    WHERE
        P.interested = 1
    ORDER BY
        CD.lastname, CD.firstname;
EOD;
$report['queries']['credentials'] =<<<'EOD'
SELECT
        P.badgeid,
        C.credentialname
    FROM
             Participants P
        JOIN ParticipantHasCredential PHC USING (badgeid)
        JOIN Credentials C USING (credentialid)
    WHERE
        P.interested = 1
    ORDER BY
        P.badgeid, C.display_order;
EOD;
$report['xsl'] =<<<'EOD'
<?xml version="1.0" encoding="UTF-8" ?>
<xsl:stylesheet version="1.1" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
    <xsl:output encoding="UTF-8" indent="yes" method="html" />
    <xsl:include href="xsl/reportInclude.xsl" />
    <xsl:template match="/">
        <xsl:choose>
            <xsl:when test="doc/query[@queryName='participants']/row">
                <table id="reportTable" class="report">
                    <thead>
                        <tr style="height:2.6rem">
                            <th class="report">Name</th>
                            <th></th>
                            <th class="report">Name for Publications</th>
                            <th></th>
                            <th class="report">Email address</th>
                            <th class="report">Person ID</th>
                            <th class="report">Professional Qualifications</th>
                        </tr>
                    </thead>
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
            <td class="report"><xsl:value-of select="@nameSort"/></td>
            <td class="report"><xsl:value-of select="@pubsname"/></td>
            <td class="report"><xsl:value-of select="@pubsnameSort"/></td>
            <td class="report"><xsl:value-of select="@email"/></td>
            <td class="report"><xsl:call-template name="showBadgeid"><xsl:with-param name="badgeid" select="@badgeid"/></xsl:call-template></td>
            <td class="report">
                <xsl:for-each select="/doc/query[@queryName = 'credentials']/row[@badgeid = $badgeid]">
                    <div><xsl:value-of select="@credentialname"/></div>
                </xsl:for-each>
            </td>
        </tr>
    </xsl:template>
</xsl:stylesheet>
EOD;
