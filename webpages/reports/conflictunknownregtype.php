<?php
// Copyright (c) 2018 Peter Olszowka. All rights reserved. See copyright document for more details.
$report = [];
$report['name'] = 'Conflict Report - Unknown RegTypes';
$report['multi'] = 'true';
$report['output_filename'] = 'unknown_reg_type.csv';
$report['description'] = 'Registration types that the system does not recognize';
$report['categories'] = array(
    'Conflict Reports' => 180,
    'Administration Reports' => 490,
);
$report['queries'] = [];
$report['queries']['regtypes'] =<<<'EOD'
SELECT 
        count(*) as count_all, C.regtype
    FROM
                  CongoDump C 
        LEFT JOIN RegTypes R USING (regtype)
    WHERE
            R.regtype IS NULL
        AND C.regtype IS NOT NULL
        AND C.regtype != ''
    GROUP BY
        C.Regtype
    ORDER BY
        C.Regtype;
EOD;
$report['xsl'] =<<<'EOD'
<?xml version="1.0" encoding="UTF-8" ?>
<xsl:stylesheet version="1.1" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
    <xsl:output encoding="UTF-8" indent="yes" method="html" />
    <xsl:include href="xsl/reportInclude.xsl" />
    <xsl:template match="/">
        <xsl:choose>
            <xsl:when test="doc/query[@queryName='regtypes']/row">
                <table id="reportTable" class="table table-sm">
                    <thead>
                        <tr>
                            <th style="">Reg Types</th>
                            <th style="">Count</th>
                        </tr>
                    </thead>
                    <tbody>
                        <xsl:apply-templates select="doc/query[@queryName='regtypes']/row" />
                    </tbody>
                </table>
            </xsl:when>
            <xsl:otherwise>
                <div class="text-info">No results found.</div>
            </xsl:otherwise>
        </xsl:choose>
    </xsl:template>

    <xsl:template match="doc/query[@queryName='regtypes']/row">
        <tr>
            <td><xsl:value-of select="@regtype" /></td>
            <td><xsl:value-of select="@count_all" /></td>
        </tr>
    </xsl:template>
</xsl:stylesheet>
EOD;
