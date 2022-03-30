<?php
// Copyright (c) 2018 Peter Olszowka. All rights reserved. See copyright document for more details.
$report = [];
$report['name'] = 'Conflict Report - Room Schedule Overlaps';
$report['multi'] = 'true';
$report['output_filename'] = 'conflict_room_overlaps.csv';
$report['description'] = 'Find any pairs of sessions whose times overlap in the same room.';
$report['categories'] = array(
    'Conflict Reports' => 120,
);
$report['queries'] = [];
$report['queries']['conflict'] =<<<'EOD'
SELECT
        R.roomid,
        R.roomname,
        SA.title AS titleA,
        Asess AS sessionidA,
        DATE_FORMAT(ADDTIME('$ConStartDatim$',Astart),'%a %l:%i %p') AS starttimeA,
        DATE_FORMAT(ADDTIME('$ConStartDatim$',Aend),'%a %l:%i %p') AS endtimeA,
        SB.title AS titleB,
        Bsess AS sessionidB,
        DATE_FORMAT(ADDTIME('$ConStartDatim$',Bstart),'%a %l:%i %p') AS starttimeB,
        DATE_FORMAT(ADDTIME('$ConStartDatim$',Bend),'%a %l:%i %p') AS endtimeB
    FROM
            Sessions SA,
            Sessions SB,
            Rooms R,
            (SELECT
                     A.roomid,
                     A.sessionid AS Asess,
                     A.starttime AS Astart,
                     ADDTIME(A.starttime, SA.duration) AS Aend,
                     B.sessionid AS Bsess,
                     B.starttime AS Bstart,
                     ADDTIME(B.starttime, SB.duration) AS Bend
                 FROM
                     Schedule A,
                     Schedule B,
                     Sessions SA,
                     Sessions SB
                 WHERE
                         A.roomid = B.roomid
                     AND A.starttime<=B.starttime
                     AND ADDTIME(A.starttime, SA.duration)>B.starttime
                     AND A.sessionid<>B.sessionid
                     AND A.sessionid=SA.sessionid
                     AND B.sessionid=SB.sessionid)
                 AS Foo
    WHERE
            Foo.roomid = R.roomid
        AND Foo.Asess=SA.sessionid
        AND Foo.Bsess=SB.sessionid;
EOD;
$report['xsl'] =<<<'EOD'
<?xml version="1.0" encoding="UTF-8" ?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
    <xsl:output encoding="UTF-8" indent="yes" method="xml" />
    <xsl:include href="xsl/reportInclude.xsl" />
    <xsl:template match="/">
        <xsl:choose>
            <xsl:when test="doc/query[@queryName='conflict']/row">
                <table id="reportTable" class="table table-sm table-bordered">
                    <thead>
                        <tr>
                            <th>Room</th>
                            <th>Session Id A</th>
                            <th>Session Title A</th>
                            <th>Start Time A</th>
                            <th>End Time A</th>
                            <th>Session Id B</th>
                            <th>Session Title B</th>
                            <th>Start Time B</th>
                            <th>End Time B</th>
                        </tr>
                    </thead>
                    <tbody>
                        <xsl:apply-templates select="doc/query[@queryName='conflict']/row" />
                    </tbody>
                </table>
            </xsl:when>
            <xsl:otherwise>
                <div class="alert alert-danger">No results found.</div>
            </xsl:otherwise>
        </xsl:choose>
    </xsl:template>

    <xsl:template match="doc/query[@queryName='conflict']/row">
        <tr>
            <td>
                <xsl:call-template name="showRoomName">
                    <xsl:with-param name="roomid" select = "@roomid" />
                    <xsl:with-param name="roomname" select = "@roomname" />
                </xsl:call-template>
            </td>
            <td><xsl:call-template name="showSessionid"><xsl:with-param name="sessionid" select = "@sessionidA" /></xsl:call-template></td>
            <td>
                <xsl:call-template name="showSessionTitle">
                    <xsl:with-param name="sessionid" select = "@sessionidA" />
                    <xsl:with-param name="title" select = "@titleA" />
                </xsl:call-template>
            </td>
            <td><xsl:value-of select="@starttimeA" /></td>
            <td><xsl:value-of select="@endtimeA" /></td>
            <td><xsl:call-template name="showSessionid"><xsl:with-param name="sessionid" select = "@sessionidB" /></xsl:call-template></td>
            <td>
                <xsl:call-template name="showSessionTitle">
                    <xsl:with-param name="sessionid" select = "@sessionidB" />
                    <xsl:with-param name="title" select = "@titleB" />
                </xsl:call-template>
            </td>
            <td><xsl:value-of select="@starttimeB" /></td>
            <td><xsl:value-of select="@endtimeB" /></td>    </tr>
    </xsl:template>
</xsl:stylesheet>
EOD;
