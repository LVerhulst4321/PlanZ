<?php
// Copyright (c) 2018-2019 Peter Olszowka. All rights reserved. See copyright document for more details.
$report = [];
$report['name'] = 'All Permissions Report';
$report['multi'] = 'true';
$report['output_filename'] = 'all_permissions.csv';
$report['description'] = 'List all permissions and phases.';
$report['categories'] = array(
    'PlanZ Administration Reports' => 160,
);
$report['columns'] = array(
);
$report['queries'] = [];
$report['queries']['permissions'] =<<<'EOD'
SELECT
        P.permissionid,
        P.permatomid,
        P.phaseid,
        P.permroleid,
        P.badgeid,
        PA.permatomtag,
        PH.phasename,
        PR.permrolename
    FROM 
                  Permissions P
        LEFT JOIN PermissionAtoms PA USING (permatomid)
        LEFT JOIN Phases PH USING (phaseid)
        LEFT JOIN PermissionRoles PR USING (permroleid)
    ORDER BY
        PH.phaseid, PA.permatomtag, PR.permrolename;
EOD;
$report['xsl'] =<<<'EOD'
<?xml version="1.0" encoding="UTF-8" ?>
<xsl:stylesheet version="1.1" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
    <xsl:output encoding="UTF-8" indent="yes" method="html" />
    <xsl:include href="xsl/reportInclude.xsl" />
    <xsl:template match="/">
        <xsl:choose>
            <xsl:when test="doc/query[@queryName='permissions']/row">
                <table id="reportTable" class="table table-sm table-bordered">
                    <thead>
                        <tr style="height:2.6rem">
                            <th>Permission ID</th>
                            <th>Permatomtag ID</th>
                            <th>Phase ID</th>
                            <th>Permission Roles ID</th>
                            <th>Person ID</th>
                            <th>Permatomtag Name</th>
                            <th>Phase Name</th>
                            <th>Permission Roles Name</th>
                        </tr>
                    </thead>
                    <tbody>
                        <xsl:apply-templates select="/doc/query[@queryName='permissions']/row"/>
                    </tbody>
                </table>
            </xsl:when>
            <xsl:otherwise>
                <div class="alert alert-danger">No results found.</div>
            </xsl:otherwise>
        </xsl:choose>
    </xsl:template>
    <xsl:template match="/doc/query[@queryName='permissions']/row">
        <tr>
            <td><xsl:value-of select="@permissionid"/></td>
            <td><xsl:value-of select="@permatomid"/></td>
            <td><xsl:value-of select="@phaseid"/></td>
            <td><xsl:value-of select="@permroleid"/></td>
            <td><xsl:value-of select="@badgeid"/></td>
            <td><xsl:value-of select="@permatomtag"/></td>
            <td><xsl:value-of select="@phasename"/></td>
            <td><xsl:value-of select="@permrolename"/></td>
        </tr>
    </xsl:template>
</xsl:stylesheet>
EOD;
