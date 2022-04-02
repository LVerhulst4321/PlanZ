<?php
// Copyright (c) 2018 Peter Olszowka. All rights reserved. See copyright document for more details.
$report = [];
$report['name'] = 'Categorized Session Count Report 3';
$report['multi'] = 'true';
$report['output_filename'] = 'category_sess_count_3.csv';
$report['description'] = 'Show count of how many sessions each participant is scheduled for broken down by division (disregarding signings)';
$report['categories'] = array(
    'Registration Reports' => 1170,
);
$report['queries'] = [];
$report['queries']['participants'] =<<<'EOD'
SELECT
        P.pubsname,
        P.badgeid,
        CD.regtype,
        CD.email,
        CD.lastname,
        CD.firstname, 
        IFNULL(subQ.total, 0) AS total,
        IFNULL(subQ.py, 0) AS py,
        IFNULL(subQ.ev, 0) AS ev,
        IFNULL(subQ.gl, 0) AS gl,
        IFNULL(subQ.gt, 0) AS gt,
        IFNULL(subQ.grpg, 0) AS grpg, 
        IFNULL(subQ.total, 0) - IFNULL(subQ.py, 0) - IFNULL(subQ.ev, 0) - IFNULL(subQ.gl, 0)
            - IFNULL(subQ.gt, 0) - IFNULL(subQ.grpg, 0) AS other
    FROM
                  Participants P
             JOIN CongoDump CD USING (badgeid)
        LEFT JOIN (
            SELECT
                    POS.badgeid,
                    Count(*) AS total,
                    SUM(IF((S.divisionid=2 OR S.divisionid=8),1,0)) AS py, /* programming or teen divisions */
                    SUM(IF((S.divisionid=3),1,0)) AS ev, /* events divisions */
                    SUM(IF((S.divisionid=9 AND S.typeid = 26),1,0)) AS gl, /* gaming division and LARP type */
                    SUM(IF((S.divisionid=9 AND S.typeid = 27),1,0)) AS gt, /* gaming division and board game type */
                    SUM(IF((S.divisionid=9 AND S.typeid = 28),1,0)) AS grpg /* gaming division and tabletop rpg type */
                FROM
                         Schedule SCH
                    JOIN ParticipantOnSession POS USING (sessionid)
                    JOIN Sessions S USING (sessionid)
                WHERE
                        S.typeid != 6 /* signing */
                    AND S.pubstatusid = 2 /* Public */
                GROUP BY POS.badgeid    
            ) AS subQ USING (badgeid)
    WHERE
        P.interested = 1
    ORDER BY
        CD.regtype, subQ.total;

EOD;
$report['queries']['sessions'] =<<<'EOD'
SELECT
        S.title,
        D.divisionname,
        TY.typename,
        TR.trackname,
        POS.badgeid,
        S.sessionid,
        DATE_FORMAT(ADDTIME('$ConStartDatim$',SCH.starttime),'%a %l:%i %p') AS starttime
    FROM
             Schedule SCH
        JOIN Sessions S USING (sessionid)
        JOIN Divisions D USING (divisionid)
        JOIN Types TY USING (typeid)
        JOIN Tracks TR USING (trackid)
        JOIN ParticipantOnSession POS USING (sessionid)
    WHERE
            S.pubstatusid = 2 /* Public */
        AND S.typeid != 6 /* signing */
    ORDER BY
        SCH.starttime;
EOD;
$report['queries']['otherSessions'] =<<<'EOD'
SELECT
        S.title,
        D.divisionname,
        TY.typename,
        TR.trackname,
        POS.badgeid, 
        DATE_FORMAT(ADDDATE('$ConStartDatim$', SCH.starttime), "%a %l:%i %p") AS starttime
    FROM
             Schedule SCH
        JOIN Sessions S USING (sessionid)
        JOIN Divisions D USING (divisionid)
        JOIN Types TY USING (typeid)
        JOIN Tracks TR USING (trackid)
        JOIN ParticipantOnSession POS USING (sessionid)
    WHERE
           (     S.divisionid NOT IN (2,3,8)          /* not programming-2,events-3,teen-8 */
             OR (S.divisionid = 9 AND S.typeid NOT IN (26, 27, 28))    /* gaming */
           ) AND S.pubstatusid = 2 /* Public */
             AND S.typeid != 6 /* signing */
    ORDER BY
        SCH.starttime;
EOD;
$report['queries']['permissionRoles'] =<<<'EOD'
SELECT
        PR.permrolename,
        P.badgeid
    FROM
             Participants P
        JOIN UserHasPermissionRole UHPR USING (badgeid)
        JOIN PermissionRoles PR USING (permroleid)
    WHERE 
            P.interested = 1
        AND PR.permroleid NOT IN (1,2) /* administrator, staff */
        AND EXISTS (
            SELECT *
                FROM
                         Schedule SCH
                    JOIN ParticipantOnSession POS USING (sessionid)
                WHERE
                    POS.badgeid = P.badgeid
                )
     ORDER BY P.badgeid;
EOD;
$report['xsl'] =<<<'EOD'
<?xml version="1.0" encoding="UTF-8" ?>
<xsl:stylesheet version="1.1" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
    <xsl:output encoding="UTF-8" indent="yes" method="html" />
    <xsl:include href="xsl/reportInclude.xsl" />
    <xsl:template match="/">
        <xsl:choose>
            <xsl:when test="doc/query[@queryName='participants']/row">
                <style>
                    td.noborder {
                        border:none !important;
                        border-collapse: separate;
                        }
                    span.day, span.sessionid {
                        display: inline-block;
                        width: 9em;
                        }
                    tr.mainrow td {
                        border-top: 2px solid black;
                        }
                    table.noleftborder {
                        border-left: none !important;
                        }
                </style>
                <table id="reportTable" class="table table-sm table-bordered">
                    <col style="width:4.75em;" />
                    <col style="width:12em;" />
                    <col style="width:10em;" />
                    <col style="width:8em;" />
                    <col style="width:12em;" />
                    <col style="width:8.5em;" />
                    <col style="width:7em;" />
                    <col style="width:4.5em;" />
                    <col style="width:4.5em;" />
                    <col style="width:4.5em;" />
                    <col style="width:4.5em;" />
                    <col style="width:4.5em;" />
                    <col style="width:14em;" />
                    <thead>
                        <tr>
                            <th >Person ID</th>
                            <th >Publication Name</th>
                            <th >Last Name</th>
                            <th >First Name</th>
                            <th >Email</th>
                            <th >Registration Type</th>
                            <th >Prog. or Youth</th>
                            <th >Events</th>
                            <th >LARPs</th>
                            <th >Board G's</th>
                            <th >TT RPG's</th>
                            <th >Other</th>
                            <th >Participant Role(s)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <xsl:apply-templates select="doc/query[@queryName='participants']/row"/>
                    </tbody>
                </table>
            </xsl:when>
            <xsl:otherwise>
                <div class="alert alert-danger">No results found.</div>
            </xsl:otherwise>
        </xsl:choose>
    </xsl:template>
    <xsl:template match="doc/query[@queryName='participants']/row">
        <xsl:variable name="badgeid" select="@badgeid" />
        <tr class="mainrow">
            <td><xsl:value-of select="@badgeid" /></td>
            <td>
                <xsl:call-template name="showPubsname">
                    <xsl:with-param name="badgeid" select = "@badgeid" />
                    <xsl:with-param name="pubsname" select = "@pubsname" />
                </xsl:call-template>
            </td>
            <td><xsl:value-of select="@lastname" /></td>
            <td><xsl:value-of select="@firstname" /></td>
            <td><xsl:value-of select="@email" /></td>
            <td><xsl:value-of select="@regtype" /></td>
            <td><xsl:value-of select="@py" /></td>
            <td><xsl:value-of select="@ev" /></td>
            <td><xsl:value-of select="@gl" /></td>
            <td><xsl:value-of select="@gt" /></td>
            <td><xsl:value-of select="@grpg" /></td>
            <td><xsl:value-of select="@other" /></td>
            <td><xsl:apply-templates select="/doc/query[@queryName='permissionRoles']/row[@badgeid=$badgeid]"/></td>
        </tr>
        <xsl:if test="@total&gt;0 and @total&lt;3">
            <xsl:apply-templates select="/doc/query[@queryName='sessions']/row[@badgeid=$badgeid]"/>
        </xsl:if>
    </xsl:template>
    <xsl:template match="/doc/query[@queryName='permissionRoles']/row">
        <div><xsl:value-of select="@permrolename" /></div>
    </xsl:template>
    <xsl:template match="/doc/query[@queryName='sessions']/row">
        <tr>
            <td colspan="2" class="noborder"><xsl:text disable-output-escaping="yes">&amp;nbsp;</xsl:text></td>
            <td colspan="11">
                <span class="day"><xsl:value-of select="@starttime" /></span>
                <span class="sessionid">
                    <xsl:call-template name="showSessionid">
                        <xsl:with-param name="sessionid" select = "@sessionid" />
                    </xsl:call-template>
                </span>
                <span class="title">
                    <xsl:call-template name="showSessionTitle">
                        <xsl:with-param name="sessionid" select = "@sessionid" />
                        <xsl:with-param name="title" select = "@title" />
                    </xsl:call-template>
                </span>
            </td>
        </tr>
    </xsl:template>
</xsl:stylesheet>
EOD;
