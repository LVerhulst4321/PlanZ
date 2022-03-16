<?php
// Copyright (c) 2018 Peter Olszowka. All rights reserved. See copyright document for more details.
$report = [];
$report['name'] = 'Session Notes';
$report['multi'] = 'true';
$report['output_filename'] = 'session_notes.csv';
$report['description'] = 'Interesting info on a Session for sessions whose status is one of EditMe, Brainstorm, Vetted, Assigned, or Scheduled';
$report['categories'] = array(
    'Programming Reports' => 1000,
);
$report['queries'] = [];
$report['queries']['sessions'] =<<<'EOD'
SELECT
        S.sessionid,
        S.title,
        T.trackname,
        IF (invitedguest,'yes','no') AS invitedguest,
        S.servicenotes,
        S.notesforprog
    FROM
             Sessions S
        JOIN Tracks T USING (trackid) 
   WHERE
            S.statusid in (1, 2, 3, 6, 7) ## Brainstorm, Vetted, Scheduled, Edit Me, Assigned
        AND (
                S.invitedguest=1
             OR LENGTH(IFNULL(S.notesforprog,""))>0
             OR LENGTH(IFNULL(S.servicenotes,""))>0
            )
    ORDER BY
        S.sessionid;
EOD;
$report['xsl'] =<<<'EOD'
<?xml version="1.0" encoding="UTF-8" ?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
    <xsl:output encoding="UTF-8" indent="yes" method="xml" />
    <xsl:include href="xsl/reportInclude.xsl" />
    <xsl:template match="/">
        <xsl:choose>
            <xsl:when test="doc/query[@queryName='sessions']/row">
                <table id="reportTable" class="table table-sm table-bordered">
                    <thead>
                        <tr>
                            <th>Session ID</th>
                            <th>Title</th>
                            <th>Track</th>
                            <th>Invited Guest Only</th>
                            <th>Hotel and Tech Notes</th>
                            <th>Notes for Programming</th>
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
            <td><xsl:call-template name="showSessionid"><xsl:with-param name="sessionid" select = "@sessionid" /></xsl:call-template></td>
            <td>
                <xsl:call-template name="showSessionTitle">
                    <xsl:with-param name="sessionid" select = "@sessionid" />
                    <xsl:with-param name="title" select = "@title" />
                </xsl:call-template>
            </td>
            <td><xsl:value-of select="@trackname"/></td>
            <td><xsl:value-of select="@invitedguest"/></td>
            <td><xsl:value-of select="@servicenotes"/></td>
            <td><xsl:value-of select="@notesforprog"/></td>
        </tr>
    </xsl:template>
</xsl:stylesheet>
EOD;
