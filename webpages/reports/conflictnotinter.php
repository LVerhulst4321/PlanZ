<?php
// Copyright (c) 2018-2019 Peter Olszowka. All rights reserved. See copyright document for more details.
$report = [];
$report['name'] = 'Conflict Report - People on Panels they are not Interested in';
$report['multi'] = 'true';
$report['output_filename'] = 'conflict_not_inter.csv';
$report['description'] = 'Participants appear on this report only if they have deleted their interest after being assigned to the panel.  Note, this report includes only "Panels".';
$report['categories'] = array(
    'Conflict Reports' => 100,
);
$report['columns'] = array(
    null,
    null,
    null,
    array("orderData" => 4),
    array("visible" => false),
    array("orderData" => 6),
    array("visible" => false),
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
        concat(CD.firstname,' ',CD.lastname) AS name,
        CONCAT(CD.lastname, CD.firstname) AS nameSort,
        IF(INSTR(P.pubsname, CD.lastname) > 0, CD.lastname, SUBSTRING_INDEX(P.pubsname, ' ', -1)) AS pubsnameSort
    FROM
                  Sessions S
             JOIN Tracks T USING (trackid)
             JOIN ParticipantOnSession POS USING (sessionid)
             JOIN Participants P USING (badgeid)
             JOIN CongoDump CD USING (badgeid)
        LEFT JOIN ParticipantSessionInterest PSI USING (sessionid, badgeid)
    WHERE
            PSI.sessionid IS NULL
        AND S.typeid = 1 ## Panel
        AND S.statusid NOT IN (4,5,10) ## Duplicate, Cancelled, or Dropped
    ORDER BY
        T.trackname, S.sessionid;
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
                        </tr>
                    </thead>
                    <tbody>
                        <xsl:apply-templates select="/doc/query[@queryName='participants']/row"/>
                    </tbody>
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
        </tr>
    </xsl:template>
</xsl:stylesheet>
EOD;
