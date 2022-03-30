<?php
// Copyright (c) 2018 Peter Olszowka. All rights reserved. See copyright document for more details.
$report = [];
$report['name'] = 'Program Participants Session Counts';
$report['multi'] = 'true';
$report['output_filename'] = 'participant_sess_count.csv';
$report['description'] = 'This is a report of program participants only sorted by number of sessions they are on that are actually running, with some registration information. It is useful for cons that comp program participants based on a minimum number of panels. In this case, this report helps make sure people get their comps. Also, participants who have not earned a comp may need some kind of consideration.';
$report['categories'] = array(
    'Registration Reports' => 385,
    'Program Ops Reports' => 385,
);
$report['columns'] = array(
    array("width" => "7em"),
    array("width" => "25em", "orderData" => 2),
    array("visible" => false),
    array("width" => "8em"),
    array("width" => "12em")
);
$report['queries'] = [];
$report['queries']['participants'] =<<<'EOD'
SELECT
        P.badgeid,
        P.pubsname,
        IF(instr(P.pubsname, CD.lastname) > 0, CD.lastname, substring_index(P.pubsname, ' ', -1)) AS pubsnamesort,
        IFNULL(CD.regtype, ' ') AS regtype,
        SU.assigned
    FROM 
                  Participants P 
             JOIN CongoDump CD USING (badgeid)
             JOIN UserHasPermissionRole UHPR USING (badgeid)
        LEFT JOIN (
                SELECT
                    POS.badgeid,
                    COUNT(POS.sessionid) AS assigned
                    FROM
                             ParticipantOnSession POS
                        JOIN Schedule SCH USING (sessionid)
                        JOIN Sessions S USING (sessionid)
                    WHERE
                            S.pubstatusid = 2 /* public */
                        AND S.typeid != 6 /* Signing */
                    GROUP BY
                        POS.badgeid
                   ) AS SU USING (badgeid)
    WHERE
            UHPR.permroleid = 3 /* Program Participant */
        AND SU.assigned > 0
    ORDER BY
        CD.regtype, SU.assigned DESC, pubsnamesort;
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
                        <tr style="height:3.2em;">
                            <th>Person ID</th>
                            <th>Pubsname</th>
                            <th>Pubsnamesort</th>
                            <th>Reg Type</th>
                            <th>Number of Sessions Assigned</th>
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
            <td><xsl:call-template name="showBadgeid"><xsl:with-param name="badgeid" select="@badgeid"/></xsl:call-template></td>
            <td><xsl:value-of select="@pubsname"/></td>
            <td><xsl:value-of select="@pubsnamesort"/></td>
            <td><xsl:value-of select="@regtype"/></td>
            <td><xsl:value-of select="@assigned"/></td>
        </tr>
    </xsl:template>
</xsl:stylesheet>
EOD;
