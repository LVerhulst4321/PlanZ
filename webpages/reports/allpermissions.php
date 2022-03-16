<?php
// Copyright (c) 2018-2019 Peter Olszowka. All rights reserved. See copyright document for more details.
$report = [];
$report['name'] = 'All Permissions Report';
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
                <table class="report" id="reportTable">
                    <thead>
                        <tr style="height:2.6rem">
                            <th class="report">Permission ID</th>
                            <th class="report">Permatomtag ID</th>
                            <th class="report">Phase ID</th>
                            <th class="report">Permission Roles ID</th>
                            <th class="report">Person ID</th>
                            <th class="report">Permatomtag Name</th>
                            <th class="report">Phase Name</th>
                            <th class="report">Permission Roles Name</th>
                        </tr>
                    </thead>
                    <xsl:apply-templates select="/doc/query[@queryName='permissions']/row"/>
                </table>
            </xsl:when>
            <xsl:otherwise>
                <div class="alert alert-danger">No results found.</div>
            </xsl:otherwise>
        </xsl:choose>
    </xsl:template>
    <xsl:template match="/doc/query[@queryName='permissions']/row">
        <tr>
            <td class="report"><xsl:value-of select="@permissionid"/></td>
            <td class="report"><xsl:value-of select="@permatomid"/></td>
            <td class="report"><xsl:value-of select="@phaseid"/></td>
            <td class="report"><xsl:value-of select="@permroleid"/></td>
            <td class="report"><xsl:value-of select="@badgeid"/></td>
            <td class="report"><xsl:value-of select="@permatomtag"/></td>
            <td class="report"><xsl:value-of select="@phasename"/></td>
            <td class="report"><xsl:value-of select="@permrolename"/></td>
        </tr>
    </xsl:template>
</xsl:stylesheet>
EOD;
