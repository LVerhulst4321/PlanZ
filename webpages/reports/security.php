<?php
// Copyright (c) 2018-2019 Peter Olszowka. All rights reserved. See copyright document for more details.
$report = [];
$report['name'] = 'Security Report';
$report['multi'] = 'true';
$report['output_filename'] = 'security_req_or_age_restr.csv';
$report['description'] = 'List all sessions where age is restricted and/or security is explicitly requested';
$report['categories'] = array(
    'Security Reports' => 20,
);
$report['columns'] = array(
    array("orderData" => 1, "width" => "7em"),
    array("visible" => false),
    array("width" => "6em", "orderData" => 3),
    array("visible" => false),
    array(),
    array(),
    array(),
    array(),
    array(),
    array()
);
$report['queries'] = [];
$report['queries']['sessions'] =<<<'EOD'
SELECT
        DATE_FORMAT(ADDTIME('$ConStartDatim$',SCH.starttime),'%a %l:%i %p') AS starttime,
        DATE_FORMAT(S.duration,'%i') AS durationmin,
        DATE_FORMAT(S.duration,'%k') AS durationhrs,
        S.duration,
        SCH.starttime AS starttimeraw,
        SCH.roomid,
        R.roomname,
        T.trackname,
        S.sessionid,
        S.title,
        SHS.serviceid,
        S.kidscatid
    FROM
                  Sessions S
             JOIN Tracks T USING (trackid)
             JOIN Schedule SCH USING (sessionid)
             JOIN Rooms R USING (roomid)
        LEFT JOIN SessionHasService SHS ON
                S.sessionid = SHS.sessionid
            AND SHS.serviceid = 21 /* Security */
    WHERE
            S.statusid = 3 /* Scheduled */
        AND (S.kidscatid = 4 /* Kids Not Allowed */
         OR SHS.sessionid IS NOT NULL)
    ORDER BY
        SCH.starttime, R.roomname;
EOD;
$report['xsl'] =<<<'EOD'
<?xml version="1.0" encoding="UTF-8" ?>
<xsl:stylesheet version="1.1" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
    <xsl:output encoding="UTF-8" indent="yes" method="html" />
    <xsl:include href="xsl/reportInclude.xsl" />
    <xsl:template match="/">
        <xsl:choose>
            <xsl:when test="doc/query[@queryName='sessions']/row">
                <table id="reportTable" class="table table-sm table-bordered">
                    <thead>
                        <tr>
                            <th>Start Time</th>
                            <th></th>
                            <th>Duration</th>
                            <th></th>
                            <th>Room</th>
                            <th>Track</th>
                            <th>Session ID</th>
                            <th>Title</th>
                            <th>Kids Not Allowed</th>
                            <th>Security Requested</th>
                        </tr>
                    </thead>
                    <tbody>
                        <xsl:apply-templates select="doc/query[@queryName='sessions']/row" />
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
            <td >
                <xsl:value-of select="@starttime" />
            </td>
            <td><xsl:value-of select="@starttimeraw" /></td>
            <td>
                <xsl:call-template name="showDuration">
                    <xsl:with-param name="durationhrs" select = "@durationhrs" />
                    <xsl:with-param name="durationmin" select = "@durationmin" />
                </xsl:call-template>
            </td>
            <td><xsl:value-of select="@duration" /></td>
            <td>
                <xsl:call-template name="showRoomName">
                    <xsl:with-param name="roomid" select = "@roomid" />
                    <xsl:with-param name="roomname" select = "@roomname" />
                </xsl:call-template>
            </td>
            <td>
                <xsl:value-of select="@trackname" />
            </td>
            <td>
                <xsl:call-template name="showSessionid">
                    <xsl:with-param name="sessionid" select = "@sessionid" />
                </xsl:call-template>
            </td>
            <td>
                <xsl:call-template name="showSessionTitle">
                    <xsl:with-param name="sessionid" select = "@sessionid" />
                    <xsl:with-param name="title" select = "@title" />
                </xsl:call-template>
            </td>
            <td>
                <xsl:if test="@kidscatid='4'">Yes</xsl:if>
            </td>
            <td>
                <xsl:if test="@serviceid">Yes</xsl:if>
            </td>
        </tr>
    </xsl:template>
</xsl:stylesheet>
EOD;
