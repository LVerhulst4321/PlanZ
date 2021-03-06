<?php
// Copyright (c) 2018 Peter Olszowka. All rights reserved. See copyright document for more details.
$report = [];
$report['name'] = 'Missing Bios Report 1';
$report['multi'] = 'true';
$report['output_filename'] = 'missing_bios.csv';
$report['description'] = 'Participants with missing or short bios with their Participant Types.';
$report['categories'] = array(
    'Publication Reports' => 2000,
);
$report['columns'] = array(
    null,
    array("orderData" => 2),
    array("visible" => false),
    null,
    null
);
$report['queries'] = [];
$report['queries']['participants'] =<<<'EOD'
SELECT
        P.badgeid,
        P.pubsname,
        CD.lastname,
        CD.firstname,
        PR.permrolename,
        P.bio,
        IF(instr(P.pubsname, CD.lastname) > 0, CD.lastname, substring_index(P.pubsname, ' ', -1)) AS pubsnamesort
    FROM
             Participants P
        JOIN CongoDump CD USING (badgeid)
        JOIN UserHasPermissionRole UHPR USING (badgeid)
        JOIN PermissionRoles PR USING (permroleid)
    WHERE
            PR.permroleid NOT IN (1, 2, 4) /* Administrator, Staff, Brainstorm */
        AND LENGTH(IFNULL(bio, "")) <= 15
        AND EXISTS ( SELECT *
                        FROM
                                 ParticipantOnSession POS
                            JOIN Schedule SCH USING (sessionid)
                            JOIN Sessions S USING (sessionid)
                        WHERE
                            POS.badgeid = P.badgeid
                            AND S.pubstatusid != 3 /* not Do Not Print */
                    )
    ORDER BY
        pubsnamesort, CD.firstname;
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
                        <tr style="height:4em;">
                            <th>Person ID</th>
                            <th>Pubs Name</th>
                            <th>Pubs Name Sort</th>
                            <th>Participant Type</th>
                            <th>Bio</th>
                        </tr>
                    </thead>
                    <tbody>
                        <xsl:apply-templates select="/doc/query[@queryName='participants']/row" />
                    </tbody>
                </table>
            </xsl:when>
            <xsl:otherwise>
                <div class="alert alert-danger">No results found.</div>
            </xsl:otherwise>
        </xsl:choose>
    </xsl:template>

    <xsl:template match="/doc/query[@queryName='participants']/row">
        <xsl:variable name="badgeid" select="@badgeid" />
        <tr>
            <td><xsl:call-template name="showBadgeid"><xsl:with-param name="badgeid" select="@badgeid"/></xsl:call-template></td>
            <td><xsl:value-of select="@pubsname"/></td>
            <td><xsl:value-of select="@pubsnamesort"/></td>
            <td><xsl:value-of select="@permrolename"/></td>
            <td><xsl:value-of select="@bio"/></td>
        </tr>
    </xsl:template>
</xsl:stylesheet>
EOD;
