<?php
$report = [];
$report['name'] = 'Anime Sessions by Time ';
$report['multi'] = 'true';
$report['output_filename'] = 'animeroom.csv';
$report['description'] = 'Just things in the Anime rooms';
$report['categories'] = array(
    'Anime Reports' => 10,
);
$report['queries'] = [];
$report['queries']['schedule'] =<<<'EOD'
SELECT
        R.roomname,
        DATE_FORMAT(ADDTIME('$ConStartDatim$',SCH.starttime),'%a %l:%i %p') as starttime,
        DATE_FORMAT(S.duration,'%k:%i') as duration,
        TY.typename,
        S.sessionid,
        S.title,
        S.progguiddesc
    FROM
             Schedule SCH
        JOIN Sessions S USING (sessionid)
        JOIN Types TY USING (typeid)
        JOIN Rooms R USING (roomid)
    WHERE
        R.function like '%Anime%'
        AND S.divisionid = 12  # Anime
    ORDER BY
        SCH.starttime;
EOD;
$report['xsl'] =<<<'EOD'
<?xml version="1.0" encoding="UTF-8" ?>
<xsl:stylesheet version="1.1" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
    <xsl:output encoding="UTF-8" indent="yes" method="html" />
    <xsl:include href="xsl/reportInclude.xsl" />
    <xsl:template match="/">
        <xsl:choose>
            <xsl:when test="doc/query[@queryName='schedule']/row">
                <table id="reportTable" class="table table-sm table-bordered">
                    <col style="width:5em;" />
                    <col style="width:8em;" />
                    <col style="width:5em;" />
                    <col style="width:6em;" />
                    <col style="width:8em;" />
                    <col style="width:20em;" />
                    <col />
                    <tr>
                        <th style="">Room Name</th>
                        <th style="">Start Time</th>
                        <th style="">Duration</th>
                        <th style="">Session ID</th>
                        <th style="">Type</th>
                        <th style="">Title</th>
                        <th style="">Description</th>
                    </tr>
                    <xsl:apply-templates select="doc/query[@queryName='schedule']/row" />
                </table>
            </xsl:when>
            <xsl:otherwise>
                <div class="alert alert-danger">No results found.</div>
            </xsl:otherwise>
        </xsl:choose>
    </xsl:template>

    <xsl:template match="doc/query[@queryName='schedule']/row">
        <tr>
            <td><xsl:value-of select="@roomname" /></td>
            <td><xsl:value-of select="@starttime" /></td>
            <td><xsl:value-of select="@duration" /></td>
            <td>
                <xsl:call-template name="showSessionid"><xsl:with-param name="sessionid" select = "@sessionid" /></xsl:call-template>
            </td>
            <td><xsl:value-of select="@typename" /></td>
            <td>
                <xsl:call-template name="showSessionTitle">
                    <xsl:with-param name="sessionid" select = "@sessionid" />
                    <xsl:with-param name="title" select = "@title" />
                </xsl:call-template>
            </td>
            <td><xsl:value-of select="@progguiddesc" /></td>
        </tr>
    </xsl:template>
</xsl:stylesheet>
EOD;
