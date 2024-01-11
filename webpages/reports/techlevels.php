<?php
// Copyright (c) 2018 Peter Olszowka. All rights reserved. See copyright document for more details.
$report = [];
$report['name'] = 'Tech levels for sessions';
$report['description'] = 'Which sessions have been assigned which tech level.';
$report['categories'] = array(
    'Programming Reports' => 1070,
    'Tech Reports' => 1070,
);
$report['queries'] = [];
$report['queries']['sessions'] =<<<'EOD'
SELECT
        S.sessionid,
        S.title,
        S.techlevelid
    FROM
        Sessions S
    ORDER BY
        S.sessionid;
EOD;
$report['queries']['techlevels'] = <<<'EOD'
SELECT
    TL.techlevelid,
    TL.techlevel
FROM
    TechLevel TL
ORDER BY
    TL.display_order;
EOD;
$report['xsl'] =<<<'EOD'
<?xml version="1.0" encoding="UTF-8" ?>
<xsl:stylesheet version="1.1" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
    <xsl:output encoding="UTF-8" indent="yes" method="html" />
    <xsl:include href="xsl/reportInclude.xsl" />
    <xsl:template match="/">
        <xsl:choose>
            <xsl:when test="doc/query[@queryName='techlevels']/row">
                <xsl:apply-templates select="/doc/query[@queryName='techlevels']/row"/>
            </xsl:when>
            <xsl:otherwise>
                <div class="alert alert-danger">No results found.</div>
            </xsl:otherwise>
        </xsl:choose>
    </xsl:template>
    <xsl:template match="/doc/query[@queryName='techlevels']/row">
        <xsl:variable name="techlevelid" select="@techlevelid" />
        <h3><xsl:value-of select="@techlevel" /></h3>
        <xsl:choose>
            <xsl:when test="count(/doc/query[@queryName='sessions']/row[@techlevelid=$techlevelid]) > 0">
                <table class="report">
                    <tr>
                        <th class="report">Session ID</th>
                        <th class="report">Title</th>
                    </tr>
                    <xsl:apply-templates select="/doc/query[@queryName='sessions']/row[@techlevelid=$techlevelid]" />
                </table>
            </xsl:when>
            <xsl:otherwise>
                <div>No items.</div>
            </xsl:otherwise>
        </xsl:choose>
    </xsl:template>
    <xsl:template match="/doc/query[@queryName='sessions']/row">
        <tr>
            <td class="report">
                <xsl:call-template name="showSessionid">
                    <xsl:with-param name="sessionid" select = "@sessionid" />
                </xsl:call-template>
            </td>
            <td class="report">
                <xsl:call-template name="showSessionTitle">
                    <xsl:with-param name="sessionid" select = "@sessionid" />
                    <xsl:with-param name="title" select = "@title" />
                </xsl:call-template>
            </td>
        </tr>
    </xsl:template>
</xsl:stylesheet>
EOD;
