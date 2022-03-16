<?php
$report = [];
$report['name'] = 'RoomSet Configuration';
$report['description'] = 'Display the details of the roomset configuration.';
$report['categories'] = array(
    'Hotel Reports' => 1,
);
$report['queries'] = [];
$report['queries']['roomsets'] =<<<'EOD'
SELECT
        roomsetid,
        roomsetname,
        description
    FROM
        RoomSets
EOD;
$report['xsl'] =<<<'EOD'
<?xml version="1.0" encoding="UTF-8" ?>
<xsl:stylesheet version="1.1" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
    <xsl:output encoding="UTF-8" indent="yes" method="html" />
    <xsl:include href="xsl/reportInclude.xsl" />
    <xsl:template match="/">
        <xsl:choose>
            <xsl:when test="doc/query[@queryName='roomsets']/row">
                <table class="report">
                    <tr>
                        <th class="report">RoomSet Id</th>
                        <th class="report">RoomSet Name</th>
                        <th class="report">RoomSet Description</th>
                    </tr>
                    <xsl:apply-templates select="doc/query[@queryName='roomsets']/row"/>
                </table>
            </xsl:when>
            <xsl:otherwise>
                <div class="alert alert-danger">No results found.</div>
            </xsl:otherwise>
        </xsl:choose>
    </xsl:template>

    <xsl:template match="doc/query[@queryName='roomsets']/row">
        <tr>
            <td class="report"><xsl:value-of select="@roomsetid"/></td>
            <td class="report"><xsl:value-of select="@roomsetname"/></td>
            <td class="report"><xsl:value-of select="@description"/></td>
        </tr>
    </xsl:template>
</xsl:stylesheet>
EOD;
