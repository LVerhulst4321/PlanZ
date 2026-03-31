<?php
// Print-ready A4 program schedule sheets, one participant per page.
$report = [];
$report['name'] = 'Program Participant Schedule Sheets (A4)';
$report['description'] = 'Print A4 schedule sheets showing program schedule for all program participants, one per page.';
$report['categories'] = array(
    'Publication Reports' => 881,
);
$report['queries'] = [];
$report['queries']['participants'] =<<<'EOD'
SELECT
        P.badgeid,
        CD.badgename
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
                        POS.badgeid = P.badgeid
                    AND S.pubstatusid = 2 /* Published */
            )
    ORDER BY
         CD.badgename;
EOD;

$report['queries']['sessions'] =<<<'EOD'
SELECT
        POS.badgeid, POS.moderator, POS.sessionid, R.roomname, S.title,
        DATE_FORMAT(ADDTIME('$ConStartDatim$',SCH.starttime),'%a %l:%i %p') AS starttime
    FROM
             ParticipantOnSession POS
        JOIN Schedule SCH USING (sessionid)
        JOIN Sessions S USING (sessionid)
        JOIN Rooms R USING (roomid)
    WHERE
        S.pubstatusid = 2 /* Published */
    ORDER BY
        POS.badgeid, SCH.starttime, POS.sessionid;
EOD;

$report['xsl'] =<<<'EOD'
<?xml version="1.0" encoding="UTF-8" ?>
<xsl:stylesheet version="1.1" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
    <xsl:output encoding="UTF-8" indent="yes" method="html" />
    <xsl:include href="xsl/reportInclude.xsl" />
    <xsl:template match="/">
        <link rel="stylesheet" href="css/zambia_report_print.css" type="text/css" />
        <style>
            @media print {
                .schedule-sheet {
                    page-break-after: always;
                }
                .schedule-sheet:last-child {
                    page-break-after: avoid;
                }
            }
            .schedule-sheet {
                max-width: 18cm;
                margin: 0 auto;
                padding: 1cm 0;
            }
            .schedule-sheet h2 {
                margin: 0 0 0.2em 0;
                font-size: 16pt;
            }
            .schedule-sheet .badge-id {
                font-size: 12pt;
                color: #555;
                margin-bottom: 0.5em;
            }
            .schedule-sheet table {
                width: 100%;
                border-collapse: collapse;
                font-size: 12pt;
            }
            .schedule-sheet th {
                text-align: left;
                border-bottom: 2px solid #333;
                padding: 0.3em 0.5em;
            }
            .schedule-sheet td {
                border-bottom: 1px solid #ccc;
                padding: 0.3em 0.5em;
                vertical-align: top;
                white-space: nowrap;
            }
            .schedule-sheet td.session-title {
                white-space: normal;
            }
            .schedule-sheet td.moderator {
                font-weight: bold;
            }
        </style>
        <xsl:choose>
            <xsl:when test="doc/query[@queryName='participants']/row">
                <div class="form">
                    <p>This report produces print-ready A4 schedule sheets for program participants, one per page.</p>
                </div>
                <div id="sheets">
                    <xsl:apply-templates select="doc/query[@queryName='participants']/row" />
                </div>
            </xsl:when>
            <xsl:otherwise>
                <div class="alert alert-danger">No results found.</div>
            </xsl:otherwise>
        </xsl:choose>
    </xsl:template>

    <xsl:template match="doc/query[@queryName='participants']/row">
        <xsl:variable name="badgeid" select="@badgeid" />
        <div class="schedule-sheet" id="{@badgeid}">
            <h2><xsl:value-of select="@badgename" /></h2>
            <div class="badge-id">Badge no: <xsl:value-of select="@badgeid" /></div>
            <table>
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>Room</th>
                        <th>Session</th>
                        <th>Role</th>
                    </tr>
                </thead>
                <tbody>
                    <xsl:apply-templates select="/doc/query[@queryName='sessions']/row[@badgeid=$badgeid]" />
                </tbody>
            </table>
        </div>
    </xsl:template>

    <xsl:template match="doc/query[@queryName='sessions']/row">
        <tr>
            <td><xsl:value-of select="@starttime" /></td>
            <td><xsl:value-of select="@roomname" /></td>
            <td class="session-title"><xsl:value-of select="@title" /></td>
            <td>
                <xsl:if test="@moderator='1'">
                    <xsl:text>Mod</xsl:text>
                </xsl:if>
            </td>
        </tr>
    </xsl:template>
</xsl:stylesheet>
EOD;
