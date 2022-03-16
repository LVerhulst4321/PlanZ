<?php
// Copyright (c) 2018-2019 Peter Olszowka. All rights reserved. See copyright document for more details.
$report = [];
$report['name'] = 'Published Authors';
$report['multi'] = 'true';
$report['output_filename'] = 'published_authors.csv';
$report['description'] = 'Participants who indicated they were published authors.';
$report['categories'] = array(
    'Participant Info Reports' => 105,
);
$report['columns'] = array(
    null,
    null,
    null,
    null
);
$report['queries'] = [];
$report['queries']['participants'] =<<<'EOD'
SELECT
        CD.badgeid,
        CD.firstname,
        CD.lastname,
        CD.badgename,
        P.pubsname
    FROM
             CongoDump CD
        JOIN Participants P USING (badgeid)
        JOIN ParticipantHasCredential PHC USING (badgeid)
    WHERE
            PHC.credentialid = 4 /* Published Author */
        AND	P.interested = 1
    ORDER BY
        IF(INSTR(P.pubsname, CD.lastname) > 0, CD.lastname, SUBSTRING_INDEX(P.pubsname, ' ', -1)),
        CD.firstname;
EOD;
$report['xsl'] =<<<'EOD'
<?xml version="1.0" encoding="UTF-8" ?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
    <xsl:output encoding="UTF-8" indent="yes" method="xml" />
    <xsl:include href="xsl/reportInclude.xsl" />
    <xsl:template match="/">
        <xsl:choose>
            <xsl:when test="doc/query[@queryName='participants']/row">
                <table id="reportTable" class="table table-sm table-bordered">
                    <thead>
                        <tr>
                            <th style="height:2.6rem">Person Id</th>
                            <th>Pubs Name</th>
                            <th>Badge Name</th>
                            <th>Last Name, First Name</th>
                        </tr>
                    </thead>
                    <xsl:apply-templates select="/doc/query[@queryName='participants']/row"/>
                </table>
            </xsl:when>
            <xsl:otherwise>
                <div class="alert alert-danger">No results found.</div>
            </xsl:otherwise>
        </xsl:choose>
    </xsl:template>
    <xsl:template match="/doc/query[@queryName='participants']/row">
        <tr>
            <td><xsl:call-template name="showBadgeid"><xsl:with-param name="badgeid" select="@badgeid"/></xsl:call-template></td>
            <td>
                <xsl:call-template name="showLinkedPubsname">
                    <xsl:with-param name="badgeid" select = "@badgeid" />
                    <xsl:with-param name="pubsname" select = "@pubsname" />
                    <xsl:with-param name="badgename" select = "@badgename" />
                    <xsl:with-param name="name" select = "@name" />
                </xsl:call-template>
            </td>
            <td><xsl:value-of select="@badgename"/></td>
            <td><xsl:value-of select="@lastname"/><xsl:text disable-output-escaping="yes">,&amp;nbsp;</xsl:text><xsl:value-of select="@firstname"/></td>
        </tr>
    </xsl:template>
</xsl:stylesheet>
EOD;
