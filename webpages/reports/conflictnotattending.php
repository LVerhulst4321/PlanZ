<?php
// Copyright (c) 2018-2019 Peter Olszowka. All rights reserved. See copyright document for more details.
$report = [];
$report['name'] = 'Conflict Report - Not Interested People that are on Panels';
$report['multi'] = 'true';
$report['output_filename'] = 'conflictnotattending.csv';
$report['description'] = 'Lists all sessions not dropped, cancelled, or duplicate which have at least one participant assigned who is not interested in being on programming.';
$report['categories'] = array(
    'Conflict Reports' => 40,
);
$report['columns'] = array(
    null,
    null,
    null,
    array("orderData" => 4),
    array("visible" => false),
    array("orderData" => 6),
    array("visible" => false),
    null,
    null
);
$report['queries'] = [];
$report['queries']['participants'] =<<<'EOD'
SELECT
        T.trackname,
        S.sessionid,
        S.title,
        P.badgeid,
        P.pubsname,
        P.interested,
        concat(CD.firstname,' ',CD.lastname) AS name,
        CONCAT(CD.lastname, CD.firstname) AS nameSort,
        IF(INSTR(P.pubsname, CD.lastname) > 0, CD.lastname, SUBSTRING_INDEX(P.pubsname, ' ', -1)) AS pubsnameSort
   FROM
             Sessions S
        JOIN Tracks T USING (trackid)
        JOIN ParticipantOnSession POS USING (sessionid)
        JOIN Participants P USING (badgeid)
        JOIN CongoDump CD USING (badgeid)
    WHERE
            S.statusid NOT IN (4,5,10) ## Duplicate, Cancelled, or Dropped
        AND IFNULL(P.interested,0) != 1
    ORDER BY
        T.trackname, P.badgeid;
EOD;
$report['xsl'] =<<<'EOD'
<?xml version="1.0" encoding="UTF-8" ?>
<xsl:stylesheet version="1.1" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
    <xsl:output encoding="UTF-8" indent="yes" method="html" />
    <xsl:include href="xsl/reportInclude.xsl" />
    <xsl:template match="/">
        <xsl:choose>
            <xsl:when test="doc/query[@queryName='participants']/row">
                <table id="reportTable" class="table table-sm table-bordered">
                    <thead>
                        <tr style="height:2.6rem">
                            <th>Track</th>
                            <th>Session ID</th>
                            <th>Title</th>
                            <th>Pubsname</th>
                            <th></th>
                            <th>Name</th>
                            <th></th>
                            <th>Person ID</th>
                            <th><xsl:text disable-output-escaping="yes">Interested &amp;amp; Attending</xsl:text></th>
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
            <td><xsl:value-of select="@trackname"/></td>
            <td><xsl:call-template name="showSessionid"><xsl:with-param name="sessionid" select="@sessionid"/></xsl:call-template></td>
            <td>
                <xsl:call-template name="showSessionTitle">
                    <xsl:with-param name="sessionid" select = "@sessionid" />
                    <xsl:with-param name="title" select = "@title" />
                </xsl:call-template>
            </td>
            <td><xsl:value-of select="@pubsname"/></td>
            <td><xsl:value-of select="@pubsnameSort"/></td>
            <td><xsl:value-of select="@name"/></td>
            <td><xsl:value-of select="@nameSort"/></td>
            <td><xsl:call-template name="showBadgeid"><xsl:with-param name="badgeid" select="@badgeid"/></xsl:call-template></td>
            <td>
                <xsl:choose>
                    <xsl:when test="@interested='0'">Didn't respond</xsl:when>
                    <xsl:when test="@interested='1'">Yes</xsl:when>
                    <xsl:when test="@interested='2'">No</xsl:when>
                    <xsl:otherwise>Didn't log in</xsl:otherwise>
                </xsl:choose>
            </td>
        </tr>
    </xsl:template>
</xsl:stylesheet>
EOD;
