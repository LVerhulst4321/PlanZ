<?php
// Copyright (c) 2009-2019 Peter Olszowka. All rights reserved. See copyright document for more details.
$report = [];
$report['name'] = 'Pocket Program';
$report['multi'] = 'true';
$report['output_filename'] = 'pocketprogram.csv';
$report['description'] = 'Public schedule for generating pocket program';
$report['categories'] = array(
    'Reports downloadable as CSVs' => 80,
    'Publication Reports' => 40
);
$report['group_concat_expand'] = true;
$report['queries'] = [];
$report['queries']['pocket_program'] =<<<'EOD'
SELECT
        S.sessionid,
        ADDTIME('$ConStartDatim$',SCH.starttime) as sorttime,
        DATE_FORMAT(ADDTIME('$ConStartDatim$',SCH.starttime),'%a %l:%i %p') AS starttime,
        DATE_FORMAT(S.duration,'%i') AS durationmin,
        DATE_FORMAT(S.duration,'%k') AS durationhrs,
        R.roomid,
        R.roomname,
        T.trackname,
        TY.typename,
        K.kidscatname,
        S.title,
        S.progguiddesc,
        group_concat(' ',P.pubsname, if (POS.moderator=1,' (m)','')) AS 'participants',
        (select max(timestamp) from SessionEditHistory SEH where SEH.sessionid=S.sessionid) as last_updated
    FROM
        Sessions S
      INNER JOIN Schedule SCH USING (sessionid)
      INNER JOIN Rooms R USING (roomid)
      INNER JOIN Tracks T USING (trackid)
      INNER JOIN Types TY USING (typeid)
      INNER JOIN KidsCategories K USING (kidscatid)
      LEFT JOIN ParticipantOnSession POS ON SCH.sessionid=POS.sessionid 
      LEFT JOIN Participants P ON POS.badgeid=P.badgeid
    WHERE 
        S.pubstatusid = 2
    GROUP BY
        SCH.sessionid, SCH.starttime, R.roomname
    ORDER BY 
        SCH.starttime, 
        R.roomname;
EOD;
$report['xsl'] =<<<'EOD'
<?xml version="1.0" encoding="UTF-8" ?>
<xsl:stylesheet version="1.1" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
    <xsl:output encoding="UTF-8" indent="yes" method="html" />
    <xsl:include href="xsl/reportInclude.xsl" />
    <xsl:template match="/">
        <xsl:choose>
            <xsl:when test="doc/query[@queryName='pocket_program']/row">
                <table id="reportTable" class="table table-sm table-bordered">
                    <thead>
                        <tr>
                            <th>Session ID</th>
                            <th>Sort Time</th>
                            <th>Start Time</th>
                            <th>Duration</th>
                            <th>Room</th>
                            <th>Track</th>
                            <th>Type</th>
                            <th>Kids Category</th>
                            <th>Title</th>
                            <th>Description</th>
                            <th>Participants</th>
                            <th>Last Updated</th>
                        </tr>
                    </thead>
                    <tbody>
                        <xsl:apply-templates select="doc/query[@queryName='pocket_program']/row"/>
                    </tbody>
                </table>
            </xsl:when>
            <xsl:otherwise>
                <div class="alert alert-danger">No results found.</div>
            </xsl:otherwise>
        </xsl:choose>
    </xsl:template>
    <xsl:template match="doc/query[@queryName='pocket_program']/row">
        <tr>
            <td>
              <xsl:call-template name="showSessionid">
                <xsl:with-param name="sessionid" select="@sessionid" />
              </xsl:call-template>
            </td>
            <td><xsl:value-of select="@sorttime" /></td>
            <td><xsl:value-of select="@starttime" /></td>
            <td>
                <xsl:call-template name="showDuration">
                    <xsl:with-param name="durationhrs" select = "@durationhrs" />
                    <xsl:with-param name="durationmin" select = "@durationmin" />
                </xsl:call-template>
            </td>
            <td>
            <xsl:call-template name="showRoomName">
              <xsl:with-param name="roomid" select="@roomid" />
              <xsl:with-param name="roomname" select="@roomname" />
            </xsl:call-template>
          </td>
          <td><xsl:value-of select="@typename" /></td>
          <td><xsl:value-of select="@trackname" /></td>
          <td><xsl:value-of select="@kidscatname" /></td>
          <td>
            <xsl:call-template name="showSessionTitle">
              <xsl:with-param name="sessionid" select="@sessionid" />
              <xsl:with-param name="title" select="@title" />
            </xsl:call-template>
          </td>
          <td><xsl:value-of select="@progguiddesc" /></td>
          <td><xsl:value-of select="@participants" /></td>
          <td><xsl:value-of select="@last_updated" /></td>                              
        </tr>
    </xsl:template>
</xsl:stylesheet>
EOD;