<?php
$report = [];
$report['name'] = 'Room Colors for Grid';
$report['description'] = 'List all the colors that can be used on the room grid.';
$report['categories'] = array(
    'PlanZ Administration Reports' => 880,
    'Hotel Reports' => 880,
);
$report['queries'] = [];
$report['queries']['roomcolors'] =<<<'EOD'
SELECT
        RC.roomcolorid,
        RC.roomcolorname,
        RC.roomcolorcode
    FROM
        RoomColors RC
    ORDER BY
        RC.display_order;
EOD;
$report['xsl'] =<<<'EOD'
<?xml version="1.0" encoding="UTF-8" ?>
<xsl:stylesheet version="1.1" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
    <xsl:output encoding="UTF-8" indent="yes" method="html" />
    <xsl:template match="/">
        <xsl:choose>
            <xsl:when test="doc/query[@queryName='roomcolors']/row">
                <table class="report">
                    <tr>
                        <th class="report">Room Color Id</th>
                        <th class="report">Room Color Name</th>
                        <th class="report">Room Color Code</th>
                    </tr>
                    <xsl:apply-templates select="doc/query[@queryName='roomcolors']/row" />
                </table>
            </xsl:when>
            <xsl:otherwise>
                <div class="alert alert-danger">No results found.</div>
            </xsl:otherwise>
        </xsl:choose>
    </xsl:template>

    <xsl:template match="doc/query[@queryName='roomcolors']/row">
        <xsl:variable name="roomcolorcode" select="@roomcolorcode" />
        <tr>
            <td class="report"><xsl:value-of select="@roomcolorid"/></td>
            <td class="report"><xsl:value-of select="@roomcolorname"/></td>
            <td class="report" bgcolor="{roomcolorcode}"><xsl:value-of select="@roomcolorcode"/></td>
        </tr>
    </xsl:template>

</xsl:stylesheet>
EOD;
