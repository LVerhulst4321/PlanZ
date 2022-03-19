<?php
// Copyright (c) 2018 Peter Olszowka. All rights reserved. See copyright document for more details.
$report = [];
$report['name'] = 'Pubs - Participant Bio and pubname';
$report['multi'] = 'true';
$report['output_filename'] = 'pubs_bios.csv';
$report['description'] =
    'Show the id, pubsname and bio for each participant who is on at least one scheduled, public session.';
$report['categories'] = [
    'Publication Reports' => 870,
];
$report['queries'] = [];
$report['queries']['participants'] = <<<'EOD'
SELECT
        P.badgeid,
        P.pubsname,
        P.bio
    FROM
             Participants P
        JOIN CongoDump CD USING (badgeid)
    WHERE
        EXISTS (
            SELECT SCH.sessionid
                FROM
                         Schedule SCH
                    JOIN ParticipantOnSession POS USING (sessionid)
                    JOIN Sessions S USING (sessionid)
                WHERE
                        S.pubstatusid != 3 # not Do Not Print
                    AND POS.badgeid = P.badgeid
            )
    ORDER BY
        IF(instr(P.pubsname, CD.lastname) > 0, CD.lastname, substring_index(P.pubsname, ' ', -1)),
        CD.firstname;
EOD;

if (defined('USE_PRONOUNS') && USE_PRONOUNS) {
    $report['xsl'] = <<<'EOD'
    <?xml version="1.0" encoding="UTF-8" ?>
    <xsl:stylesheet version="1.1" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
        <xsl:output encoding="UTF-8" indent="yes" method="html" />
        <xsl:include href="xsl/reportInclude.xsl" />
        <xsl:template match="/">
            <xsl:choose>
                <xsl:when test="doc/query[@queryName='participants']/row">
                    <table id="reportTable" class="report table table-sm">
                        <col style="width:6em;" />
                        <col style="width:12em;" />
                        <col style="width:8em;" />
                        <col />
                        <thead>
                            <tr>
                                <th>Person Id</th>
                                <th>Name for Publications</th>
                                <th>Pronouns</th>
                                <th>Biography</th>
                            </tr>
                        </thead>
                        <tbody>
                            <xsl:apply-templates select="doc/query[@queryName='participants']/row" />
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
                <td><xsl:value-of select="@pubsname" /></td>
                <td><xsl:value-of select="@pronouns" /></td>
                <td><xsl:value-of select="@bio" /></td>
            </tr>
        </xsl:template>
    </xsl:stylesheet>
EOD;
} else {
    $report['xsl'] = <<<'EOD'
    <?xml version="1.0" encoding="UTF-8" ?>
    <xsl:stylesheet version="1.1" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
        <xsl:output encoding="UTF-8" indent="yes" method="html" />
        <xsl:include href="xsl/reportInclude.xsl" />
        <xsl:template match="/">
            <xsl:choose>
                <xsl:when test="doc/query[@queryName='participants']/row">
                    <table id="reportTable" class="report table table-sm">
                        <col style="width:6em;" />
                        <col style="width:12em;" />
                        <col />
                        <thead>
                            <tr>
                                <th>Person Id</th>
                                <th>Name for Publications</th>
                                <th>Biography</th>
                            </tr>
                        </thead>
                        <tbody>
                            <xsl:apply-templates select="doc/query[@queryName='participants']/row" />
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
                <td><xsl:value-of select="@pubsname" /></td>
                <td><xsl:value-of select="@bio" /></td>
            </tr>
        </xsl:template>
    </xsl:stylesheet>
EOD;
}
