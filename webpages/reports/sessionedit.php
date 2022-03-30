<?php
// Copyright (c) 2018 Peter Olszowka. All rights reserved. See copyright document for more details.
$report = [];
$report['name'] = 'Session Edit History Report ';
$report['multi'] = 'true';
$report['output_filename'] = 'session_edit_history.csv';
$report['description'] = 'Show the most recent edit activity for each session (sorted by time).';
$report['categories'] = array(
    'Events Reports' => 90,
    'Programming Reports' => 90,
    'Program Ops Reports' => 1,
);
$report['queries'] = [];
$report['queries']['sessions'] =<<<'EOD'
SELECT
        SEH.timestamp,
        S.sessionid,
        S.title,
        T.trackname,
        SS.statusname,
        SEC.description,
        SEH.editdescription,
        SEH.name,
        SEH.email_address
    FROM
                  Sessions S
             JOIN Tracks T USING (trackid)
             JOIN SessionStatuses SS USING (statusid)
        LEFT JOIN (SELECT
                        SEH2.sessionid,
                        MAX(SEH2.timestamp) AS timestamp
                    FROM
                        SessionEditHistory SEH2
                    GROUP BY
                        SEH2.sessionid
                  ) AS SUBQ1 ON S.sessionid = SUBQ1.sessionid
        LEFT JOIN SessionEditHistory SEH ON S.sessionid = SEH.sessionid AND SUBQ1.timestamp = SEH.timestamp
        LEFT JOIN SessionEditCodes SEC USING (sessioneditcode)
    WHERE
        S.statusid IN (1, 2, 3, 6, 7) ## Brainstorm, Vetted, Scheduled, Edit Me, Assigned
    ORDER BY
        SEH.timestamp;
EOD;
$report['xsl'] =<<<'EOD'
<?xml version="1.0" encoding="UTF-8" ?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
    <xsl:output encoding="UTF-8" indent="yes" method="html" />
    <xsl:include href="xsl/reportInclude.xsl" />
    <xsl:template match="/">
        <xsl:choose>
            <xsl:when test="doc/query[@queryName='sessions']/row">
                <table id="reportTable" class="table table-sm table-bordered">
                    <thead>
                        <tr>
                            <th>When</th>
                            <th>Session ID</th>
                            <th>Title</th>
                            <th>Track</th>
                            <th>
                                <div>Current</div>
                                <div>Status</div>
                            </th>
                            <th>Who</th>
                            <th>What</th>
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <xsl:apply-templates select="doc/query[@queryName='sessions']/row"/>
                    </tbody>
                </table>
            </xsl:when>
            <xsl:otherwise>
                <div class="alert alert-danger">No results found.</div>
            </xsl:otherwise>
        </xsl:choose>
    </xsl:template>
    <xsl:template match="doc/query[@queryName='sessions']/row">
        <tr>
            <td><xsl:value-of select="@timestamp" /></td>
            <td>
                <xsl:call-template name="showSessionid">
                    <xsl:with-param name="sessionid" select="@sessionid" />
                </xsl:call-template>
            </td>
            <td>
                <xsl:call-template name="showSessionTitle">
                    <xsl:with-param name="sessionid" select="@sessionid" />
                    <xsl:with-param name="title" select="@title" />
                </xsl:call-template>
            </td>
            <td><xsl:value-of select="@trackname" /></td>
            <td><xsl:value-of select="@statusname" /></td>
            <td><xsl:value-of select="@name" /> (<xsl:value-of select="@email_address" />)</td>
            <td><xsl:value-of select="@description" /></td>
            <td><xsl:value-of select="@editdescription" /></td>
        </tr>
    </xsl:template>
</xsl:stylesheet>
EOD;
