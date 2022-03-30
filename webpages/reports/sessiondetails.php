<?php
$report = [];
$report['name'] = 'Session Details';
$report['multi'] = 'true';
$report['output_filename'] = 'session_details.csv';
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
                <table id="reportTable" class="table table-sm table-bordered">
                    <thead>
                        <tr>
                            <th>Session ID</th>
                            <th>Division</th>
                            <th>Track</th>
                            <th>Type</th>
                            <th>Pub Status</th>
                            <th>Title</th>
                            <th>Kids Cat</th>
                            <th>Est Attend</th>
                            <th>Duration</th>
                            <th>Room Set</th>
                            <th>Status</th>
                            <th>Invited Guest Only</th>
                            <th>Sign Up Req</th>
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
            <td><xsl:value-of select="@divisionname"/></td>
            <td><xsl:value-of select="@trackname"/></td>
            <td><xsl:value-of select="@typename"/></td>
            <td><xsl:value-of select="@pubstatusname"/></td>
            <td>
                <xsl:call-template name="showSessionTitle">
                    <xsl:with-param name="sessionid" select = "@sessionid" />
                    <xsl:with-param name="title" select = "@title" />
                </xsl:call-template>
            </td>
            <td><xsl:value-of select="@kidscatname"/></td>
            <td><xsl:value-of select="@estatten"/></td>
            <td><xsl:value-of select="@duration"/></td>
            <td><xsl:value-of select="@roomsetname"/></td>
            <td><xsl:value-of select="@statusname"/></td>
            <td><xsl:value-of select="@invitedguest"/></td>
            <td><xsl:value-of select="@signupreq"/></td>
        </tr>
    </xsl:template>
</xsl:stylesheet>
EOD;
