<?php
// Copyright (c) 2018-2019 Peter Olszowka. All rights reserved. See copyright document for more details.
$report = [];
$report['name'] = 'All Privileges Report';
$report['multi'] = 'true';
$report['output_filename'] = 'all_privileges.csv';
$report['description'] = 'List all users and their permission roles';
$report['categories'] = array(
    'Administration Reports' => 170,
);
$report['columns'] = array(
    null,
    array("orderData" => 2),
    array("visible" => false),
    array("orderData" => 4),
    array("visible" => false),
    array("orderable" => false)
);
$report['queries'] = [];
$report['queries']['users'] =<<<'EOD'
SELECT
        CD.badgeid,
        P.pubsname,
        CONCAT(CD.firstname,' ',CD.lastname) AS name,
        CONCAT(CD.lastname, CD.firstname) AS nameSort,
        P.sortedpubsname AS pubsnameSort
    FROM
             CongoDump CD
        JOIN Participants P USING (badgeid) 
    ORDER BY
        CD.lastname;
EOD;
$report['queries']['user_roles'] =<<<'EOD'
SELECT
        CD.badgeid,
        PR.permrolename
    FROM
             CongoDump CD
        JOIN UserHasPermissionRole UHPR USING (badgeid)
        JOIN PermissionRoles PR USING (permroleid)
EOD;
$report['xsl'] =<<<'EOD'
<?xml version="1.0" encoding="UTF-8" ?>
<xsl:stylesheet version="1.1" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
    <xsl:output encoding="UTF-8" indent="yes" method="html" />
    <xsl:include href="xsl/reportInclude.xsl" />
    <xsl:template match="/">
        <xsl:choose>
            <xsl:when test="doc/query[@queryName='users']/row">
                <table id="reportTable" class="table table-sm table-bordered">
                    <thead>
                        <tr style="height:2.6rem">
                            <th>Person ID</th>
                            <th>Name</th>
                            <th></th>
                            <th>Name for publications</th>
                            <th></th>
                            <th>Permission roles</th>
                        </tr>
                    </thead>
                    <xsl:apply-templates select="/doc/query[@queryName='users']/row"/>
                </table>
            </xsl:when>
            <xsl:otherwise>
                <div class="alert alert-danger">No results found.</div>
            </xsl:otherwise>
        </xsl:choose>
    </xsl:template>
    <xsl:template match="/doc/query[@queryName='users']/row">
        <xsl:variable name="badgeid" select="@badgeid" />
        <tr>
            <td><xsl:call-template name="showBadgeid"><xsl:with-param name="badgeid" select="@badgeid"/></xsl:call-template></td>
            <td><xsl:value-of select="@name"/></td>
            <td><xsl:value-of select="@nameSort"/></td>
            <td><xsl:value-of select="@pubsname"/></td>
            <td><xsl:value-of select="@pubsnameSort"/></td>
            <td>
                <xsl:for-each select="/doc/query[@queryName = 'user_roles']/row[@badgeid = $badgeid]">
                    <div><xsl:value-of select="@permrolename"/></div>
                </xsl:for-each>
            </td>
        </tr>
    </xsl:template>
</xsl:stylesheet>
EOD;
