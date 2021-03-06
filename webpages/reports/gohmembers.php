<?php
// Copyright (c) 2018-2019 Peter Olszowka. All rights reserved. See copyright document for more details.
$report = [];
$report['name'] = 'GoH Members';
$report['multi'] = 'true';
$report['output_filename'] = 'goh_members.csv';
$report['description'] = 'List Members with the GoH Status.';
$report['categories'] = array(
    'Administration Reports' => 1010,
    'Security Reports' => 10,
    'GOH Reports' => 590,
);
$report['columns'] = array(
    null,
    array("orderData" => 2),
    array("visible" => false),
    array("orderData" => 4),
    array("visible" => false),
    null,
    null,
    array("orderable" => false)
);
$report['queries'] = [];
$report['queries']['staff'] =<<<'EOD'
SELECT
        badgeid,
        P.pubsname,
        CONCAT(CD.firstname,' ',CD.lastname) AS name,
        CONCAT(CD.lastname, CD.firstname) AS nameSort,
        IF (INSTR(P.pubsname, CD.lastname) > 0, CD.lastname, SUBSTRING_INDEX(P.pubsname, ' ', -1)) AS pubsnameSort,
        IF (P.password='4cb9c8a8048fd02294477fcb1a41191a','changme','OK') AS password,
        CD.email AS email
    FROM
             Participants P
        JOIN CongoDump CD using (badgeid)
        JOIN UserHasPermissionRole UHPR using (badgeid)
    WHERE
        UHPR.permroleid = 8 /* GoH */
    ORDER BY
        CD.lastname, CD.firstname;
EOD;
$report['queries']['privileges'] =<<<'EOD'
SELECT
        UHPR.badgeid,
        PR.permrolename
    FROM
             UserHasPermissionRole UHPR
        JOIN PermissionRoles PR using (permroleid)
    WHERE
        UHPR.badgeid in (SELECT badgeid FROM UserHasPermissionRole WHERE permroleid = 8);
EOD;
$report['xsl'] =<<<'EOD'
<?xml version="1.0" encoding="UTF-8" ?>
<xsl:stylesheet version="1.1" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
    <xsl:output encoding="UTF-8" indent="yes" method="html" />
    <xsl:include href="xsl/reportInclude.xsl" />
    <xsl:template match="/">
        <xsl:choose>
            <xsl:when test="doc/query[@queryName='staff']/row">
                <table id="reportTable" class="table table-sm table-bordered">
                    <thead>
                         <tr style="height:2.6rem">
                            <th>Person ID</th>
                            <th>Name</th>
                            <th></th>
                            <th>Name for publications</th>
                            <th></th>
                            <th>Password</th>
                            <th>Email</th>
                            <th>Permission roles</th>
                        </tr>
                    </thead>
                    <tbody>
                        <xsl:apply-templates select="doc/query[@queryName='staff']/row"/>
                    </tbody>
                </table>
            </xsl:when>
            <xsl:otherwise>
                <div class="alert alert-danger">No results found.</div>
            </xsl:otherwise>
        </xsl:choose>
    </xsl:template>

    <xsl:template match="doc/query[@queryName='staff']/row">
        <xsl:variable name="badgeid" select="@badgeid" />
        <tr>
            <td><xsl:call-template name="showBadgeid"><xsl:with-param name="badgeid" select="@badgeid"/></xsl:call-template></td>
            <td><xsl:value-of select="@name"/></td>
            <td><xsl:value-of select="@nameSort"/></td>
            <td><xsl:value-of select="@pubsname"/></td>
            <td><xsl:value-of select="@pubsnameSort"/></td>
            <td><xsl:value-of select="@password"/></td>
            <td><xsl:value-of select="@email"/></td>
            <td>
                <xsl:apply-templates select="/doc/query[@queryName = 'privileges']/row[@badgeid = $badgeid]"/>
            </td>
        </tr>
    </xsl:template>

    <xsl:template match="/doc/query[@queryName='privileges']/row">
        <div><xsl:value-of select="@permrolename"/></div>
    </xsl:template>
</xsl:stylesheet>
EOD;
