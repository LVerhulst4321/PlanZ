<?php
$report = [];
$report['name'] = 'Participant Count of Interests';
$report['multi'] = 'true';
$report['output_filename'] = 'participantinterestscount.csv';
$report['description'] = 'A count of interests from interested programming participants.';
$report['categories'] = array(
    'Programming Reports' => 1,
);
$report['queries'] = [];
$report['queries']['interests'] =<<<'EOD'
SELECT
        I.interestid,
        I.interestname,
        count(P.badgeid) AS partCount
    FROM
                  Interests I
        LEFT JOIN ParticipantHasInterest PHI USING (interestid)
        LEFT JOIN Participants P ON PHI.badgeid = P.badgeid AND P.interested = 1
    GROUP BY
        I.interestid
    ORDER BY
        partCount DESC;
EOD;
$report['xsl'] =<<<'EOD'
<?xml version="1.0" encoding="UTF-8" ?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
    <xsl:output encoding="UTF-8" indent="yes" method="html" />
    <xsl:include href="xsl/reportInclude.xsl" />
    <xsl:template match="/">
        <xsl:choose>
            <xsl:when test="doc/query[@queryName='interests']/row">
                <table class="table table-sm table-bordered">
                    <tr>
                        <th>Interest Name</th>
                        <th>
                            <div>Number of</div>
                            <div>Participants</div>
                        </th>
                    </tr>
                    <xsl:apply-templates select="doc/query[@queryName='interests']/row"/>
                </table>
            </xsl:when>
            <xsl:otherwise>
                <div class="alert alert-danger">No results found.</div>
            </xsl:otherwise>
        </xsl:choose>
    </xsl:template>

    <xsl:template match="doc/query[@queryName='interests']/row">
        <tr>
            <td><xsl:value-of select="@interestname" /></td>
            <td><xsl:value-of select="@partCount" /></td>
        </tr>
    </xsl:template>
</xsl:stylesheet>
EOD;
