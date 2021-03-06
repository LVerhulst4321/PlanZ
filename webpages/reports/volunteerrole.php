<?php
// Copyright (c) 2018-2019 Peter Olszowka. All rights reserved. See copyright document for more details.
$report = [];
$report['name'] = 'Volunteer Role';
$report['multi'] = 'true';
$report['output_filename'] = 'volunteer_role.csv';
$CON_NAME = CON_NAME;
$report['description'] = 'Participants who expressed a willingness to "Join $CON_NAME Volunteer Staff".';
$report['categories'] = array(
    'Participant Info Reports' => 1140,
);
$report['columns'] = array(
    null,
    array("orderData" => 2),
    array("visible" => false),
    null,
    array("orderData" => 5),
    array("visible" => false)
);
$report['queries'] = [];
$report['queries']['participants'] =<<<'EOD'
SELECT
        CD.badgeid,
        CD.firstname,
        CD.lastname,
        CONCAT(CD.lastname, CD.firstname) AS nameSort,
        CD.badgename,
        P.pubsname,
        P.sortedpubsname AS pubsnameSort
    FROM
             CongoDump CD
        JOIN Participants P USING (badgeid)
        JOIN ParticipantHasRole PHR USING (badgeid)
    WHERE
            PHR.roleid = 13 /* Volunteer */
        AND P.interested = 1
    ORDER BY
        P.sortedpubsname;
EOD;
$report['xsl'] =<<<'EOD'
<?xml version="1.0" encoding="UTF-8" ?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
    <xsl:output encoding="UTF-8" indent="yes" method="xml" />
    <xsl:include href="xsl/reportInclude.xsl" />
    <xsl:template match="/">
        <xsl:choose>
            <xsl:when test="doc/query[@queryName='participants']/row">
                <table id="reportTable" class="table table-sm table-bordered">
                    <thead>
                        <tr style="height:2.6rem">
                            <th>Person Id</th>
                            <th>Pubs Name</th>
                            <th></th>
                            <th>Badge Name</th>
                            <th>Name</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <xsl:apply-templates select="/doc/query[@queryName='participants']/row"/>
                    </tbody>
                </table>
            </xsl:when>
            <xsl:otherwise>
                <div class="alert alert-danger">No results found.</div>
            </xsl:otherwise>
        </xsl:choose>
    </xsl:template>
    <xsl:template match="/doc/query[@queryName='participants']/row">
        <tr>
            <td><xsl:call-template name="showBadgeid"><xsl:with-param name="badgeid" select="@badgeid"/></xsl:call-template></td>
            <td><xsl:value-of select="@pubsname"/></td>
            <td><xsl:value-of select="@pubsnameSort"/></td>
            <td><xsl:value-of select="@badgename"/></td>
            <td><xsl:value-of select="@firstname"/><xsl:text disable-output-escaping="yes">&amp;nbsp;</xsl:text><xsl:value-of select="@lastname"/></td>
            <td><xsl:value-of select="@nameSort"/></td>
        </tr>
    </xsl:template>
</xsl:stylesheet>
EOD;
