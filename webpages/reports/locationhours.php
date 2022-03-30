<?php
$report = [];
$report['name'] = 'Location Hours';
$report['multi'] = 'true';
$report['output_filename'] = 'location_hours.csv';
$report['description'] = 'Display the hours for the different locations of the convention that are not in the program schedule.';
$report['categories'] = array(
    'Hotel Reports' => 1,
);
$report['queries'] = [];
$report['queries']['locations'] =<<<'EOD'
SELECT
        L.locationid,
        L.locationname,
        L.roomname,
        L.locationhours
    FROM
        Locations L
    ORDER BY display_order
EOD;
$report['xsl'] =<<<'EOD'
<?xml version="1.0" encoding="UTF-8" ?>
<xsl:stylesheet version="1.1" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
    <xsl:output encoding="UTF-8" indent="yes" method="html" />
    <xsl:include href="xsl/reportInclude.xsl" />
    <xsl:template match="/">
        <xsl:choose>
            <xsl:when test="doc/query[@queryName='locations']/row">
                <table id="reportTable" class="table table-sm table-bordered">
                    <thead>
                        <tr>
                            <th>Location Id</th>
                            <th>Location Name</th>
                            <th>Room Name</th>
                            <th>Location Hours</th>
                        </tr>
                    </thead>
                    <tbody>
                        <xsl:apply-templates select="doc/query[@queryName='locations']/row"/>
                    </tbody>
                </table>
            </xsl:when>
            <xsl:otherwise>
                <div class="alert alert-danger">No results found.</div>
            </xsl:otherwise>
        </xsl:choose>
    </xsl:template>

    <xsl:template match="doc/query[@queryName='locations']/row">
        <tr>
            <td><xsl:value-of select="@locationid"/></td>
            <td><xsl:value-of select="@locationname"/></td>
            <td><xsl:value-of select="@roomname"/></td>
            <td><xsl:value-of select="@locationhours" disable-output-escaping="yes"/></td>
        </tr>
    </xsl:template>
</xsl:stylesheet>
EOD;
