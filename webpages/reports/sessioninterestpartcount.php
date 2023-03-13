<?php
// Copyright (c) 2018 Peter Olszowka. All rights reserved. See copyright document for more details.
$report = [];
$report['name'] = 'Session Interest Counts by Participant';
$report['multi'] = 'true';
$report['output_filename'] = 'session_interest_part_counts.csv';
$report['description'] = 'Just how many panels did each participant sign up for anyway? (Also counts invitations)';
$report['categories'] = array(
    'Programming Reports' => 970,
);
$report['queries'] = [];
$report['queries']['participants'] =<<<'EOD'
SELECT
        P.badgeid,
        P.pubsname,
        COUNT(sessionid) AS interested,
        CD.badgename,
        concat(CD.firstname,' ',CD.lastname) AS name,
        PA.maxprog
    FROM
                  Participants P
             JOIN CongoDump CD USING (badgeid)
        LEFT JOIN ParticipantSessionInterest PSI USING (badgeid)
        LEFT OUTER JOIN ParticipantAvailability PA USING (badgeid)
    WHERE
        P.interested = 1
        AND ((PSI.rank is not NULL
        AND PSI.rank != 0 AND PSI.rank != 6) OR PSI.willmoderate = 1)
    GROUP BY
        P.badgeid, P.pubsname, CD.badgename, name
    ORDER BY
        IF(INSTR(P.pubsname, CD.lastname) > 0, CD.lastname, SUBSTRING_INDEX(P.pubsname, ' ', -1)),
        CD.firstname;
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
                        <tr>
                            <th>Person ID</th>
                            <th>Name for Publications</th>
                            <th>Interested Sessions Count</th>
                            <th>Max Sessions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <xsl:apply-templates select="doc/query[@queryName='participants']/row"/>
                    </tbody>
                </table>
            </xsl:when>
            <xsl:otherwise>
                <div class="alert alert-danger">No results found.</div>
            </xsl:otherwise>
        </xsl:choose>
    </xsl:template>

    <xsl:template match="doc/query[@queryName='participants']/row">
        <tr>
            <td><xsl:value-of select="@badgeid" /></td>
            <td>
                <xsl:call-template name="showLinkedPubsname">
                    <xsl:with-param name="badgeid" select = "@badgeid" />
                    <xsl:with-param name="pubsname" select = "@pubsname" />
                    <xsl:with-param name="badgename" select = "@badgename" />
                    <xsl:with-param name="name" select = "@name" />
                </xsl:call-template>
            </td>
            <td class="text-center"><xsl:value-of select="@interested" /></td>
            <td class="text-center"><xsl:value-of select="@maxprog" /></td>
        </tr>
    </xsl:template>
</xsl:stylesheet>
EOD;
