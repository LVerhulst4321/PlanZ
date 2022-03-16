<?php
$report = [];
$report['name'] = 'Session Descriptions';
$report['description'] = 'Display all the different descriptions for all active sessions.';
$report['categories'] = array(
    'Programming Reports' => 1,
);
$report['queries'] = [];
$report['queries']['sessions'] =<<<'EOD'
SELECT
        sessionid,
        title,
        progguiddesc,
        persppartinfo,
        notesforpart,
        servicenotes,
        notesforprog
    FROM
             Sessions
        JOIN SessionStatuses AS SS USING (statusid)
    WHERE
        SS.statusid NOT IN (4, 5, 10) # Dropped, Cancelled, Duplicate
    ORDER BY
        sessionid
EOD;
$report['xsl'] =<<<'EOD'
<?xml version="1.0" encoding="UTF-8" ?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
    <xsl:output encoding="UTF-8" indent="yes" method="xml" />
    <xsl:include href="xsl/reportInclude.xsl" />
    <xsl:template match="/">
        <xsl:choose>
            <xsl:when test="doc/query[@queryName='sessions']/row">
                <table class="report">
                    <tr>
                        <th class="report">Session ID</th>
                        <th class="report">Title</th>
                        <th class="report">Description</th>
                        <th class="report">Prospective Participant Info</th>
                        <th class="report">Notes for Participants</th>
                        <th class="report">Notes for Tech and Hotel</th>
                        <th class="report">Notes for Programming Committee</th>
                    </tr>
                    <xsl:apply-templates select="doc/query[@queryName='sessions']/row"/>
                </table>
            </xsl:when>
            <xsl:otherwise>
                <div class="alert alert-danger">No results found.</div>
            </xsl:otherwise>
        </xsl:choose>
    </xsl:template>

    <xsl:template match="doc/query[@queryName='sessions']/row">
        <tr>
            <td class="report"><xsl:call-template name="showSessionid"><xsl:with-param name="sessionid" select = "@sessionid" /></xsl:call-template></td>
            <td class="report">
                <xsl:call-template name="showSessionTitle">
                    <xsl:with-param name="sessionid" select = "@sessionid" />
                    <xsl:with-param name="title" select = "@title" />
                </xsl:call-template>
            </td>
            <td class="report"><xsl:value-of select="@progguiddesc"/></td>
            <td class="report"><xsl:value-of select="@persppartinfo"/></td>
            <td class="report"><xsl:value-of select="@notesforpart"/></td>
            <td class="report"><xsl:value-of select="@servicenotes"/></td>
            <td class="report"><xsl:value-of select="@notesforprog"/></td>
        </tr>
    </xsl:template>
</xsl:stylesheet>
EOD;
