<?php
$report = [];
$report['name'] = 'Session Details';
$report['description'] = 'Display details for all active sessions.';
$report['categories'] = array(
    'Programming Reports' => 1,
);
$report['queries'] = [];
$report['queries']['sessions'] =<<<'EOD'
SELECT
        sessionid,
        D.divisionname,
        trackname,
        T.typename,
        PS.pubstatusname,
        title,
        KC.kidscatname,
        estatten,
        CONCAT( IF(LEFT(duration,2)=00, '', IF(LEFT(duration,1)=0, CONCAT(RIGHT(LEFT(duration,2),1),'hr '), CONCAT(LEFT(duration,2),'hr '))),
                IF(DATE_FORMAT(duration,'%i')=00, '', IF(LEFT(DATE_FORMAT(duration,'%i'),1)=0, CONCAT(RIGHT(DATE_FORMAT(duration,'%i'),1),'min'), CONCAT(DATE_FORMAT(duration,'%i'),'min')))
              ) duration,
        RS.roomsetname,
        SS.statusname,
        progguiddesc,
        persppartinfo,
        invitedguest,
        signupreq
    FROM
             Sessions S
        JOIN Tracks USING (trackid)
        JOIN SessionStatuses AS SS USING (statusid)
        JOIN RoomSets AS RS USING (roomsetid)
        JOIN Divisions AS D ON (D.divisionid = S.divisionid)
        JOIN Types AS T USING (typeid)
        JOIN PubStatuses AS PS USING (pubstatusid)
        JOIN KidsCategories AS KC USING (kidscatid)
    WHERE
        SS.statusid NOT IN (4, 5, 10) # Dropped, Cancelled, Duplicate
    ORDER BY
        trackname, SS.statusname
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
                        <th class="report">Division</th>
                        <th class="report">Track</th>
                        <th class="report">Type</th>
                        <th class="report">Pub Status</th>
                        <th class="report">Title</th>
                        <th class="report">Kids Cat</th>
                        <th class="report">Est Attend</th>
                        <th class="report">Duration</th>
                        <th class="report">Room Set</th>
                        <th class="report">Status</th>
                        <th class="report">Invited Guest Only</th>
                        <th class="report">Sign Up Req</th>
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
            <td class="report"><xsl:value-of select="@divisionname"/></td>
            <td class="report"><xsl:value-of select="@trackname"/></td>
            <td class="report"><xsl:value-of select="@typename"/></td>
            <td class="report"><xsl:value-of select="@pubstatusname"/></td>
            <td class="report">
                <xsl:call-template name="showSessionTitle">
                    <xsl:with-param name="sessionid" select = "@sessionid" />
                    <xsl:with-param name="title" select = "@title" />
                </xsl:call-template>
            </td>
            <td class="report"><xsl:value-of select="@kidscatname"/></td>
            <td class="report"><xsl:value-of select="@estatten"/></td>
            <td class="report"><xsl:value-of select="@duration"/></td>
            <td class="report"><xsl:value-of select="@roomsetname"/></td>
            <td class="report"><xsl:value-of select="@statusname"/></td>
            <td class="report"><xsl:value-of select="@invitedguest"/></td>
            <td class="report"><xsl:value-of select="@signupreq"/></td>
        </tr>
    </xsl:template>
</xsl:stylesheet>
EOD;
