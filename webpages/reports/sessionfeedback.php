<?php
// Copyright (c) 2022 BC Holmes. All rights reserved. See copyright document for more details.
$report = [];
$report['name'] = 'Interest Survey Report';
$report['multi'] = 'true';
$report['output_filename'] = 'sessionfeedback.csv';
$report['description'] = 'For each session, show number of members who expressed interest to either attend or be assigned to a session';
$report['categories'] = array(
    'Programming Reports' => 965,
    'WisCon Custom Reports' => 20,
);
$report['queries'] = [];
$report['queries']['sessions'] =<<<'EOD'
SELECT
    sessionid,
    title,
    trackname,
    sum(attend1 * 1.2 + attend2 + attend3 * 0.5) as attendPos,
    sum(attend1) as attend1,
    sum(attend2) as attend2,
    sum(attend3) as attend3,
    sum(attend4) as attend4,
    sum(attend5) as attend5,
    sum(attend_type1) as attend_type1,
    sum(attend_type2) as attend_type2,
    sum(attend_type3) as attend_type3,
    sum(rank1) as rank1,
    sum(rank2) as rank2,
    sum(rank3) as rank3,
    sum(rank4) as rank4,
    sum(rank5) as rank5,
    sum(willmoderate1) as willmoderate1,
    sum(panelist_online) as panelist_online
from 
(SELECT
        S.sessionid,
        S.title,
        T.trackname,
        T.display_order,
        case PSI.attend when 1 then 1 else 0 end as attend1,
        case PSI.attend when 2 then 1 else 0 end as attend2,
        case PSI.attend when 3 then 1 else 0 end as attend3,
        case PSI.attend when 4 then 1 else 0 end as attend4,
        case PSI.attend when 5 then 1 else 0 end as attend5,
        case PSI.attend_type when 1 then 1 else 0 end as attend_type1,
        case PSI.attend_type when 2 then 1 else 0 end as attend_type2,
        case PSI.attend_type when 3 then 1 else 0 end as attend_type3,
        case PSI.rank when 1 then 1 else 0 end as rank1,
        case PSI.rank when 2 then 1 else 0 end as rank2,
        case PSI.rank when 3 then 1 else 0 end as rank3,
        case PSI.rank when 4 then 1 else 0 end as rank4,
        case PSI.rank when 5 then 1 else 0 end as rank5,
        case PSI.willmoderate when 1 then 1 else 0 end as willmoderate1,
        case when PSI.attend_type = 3 AND PSI.rank in (1, 2, 3) then 1 else 0 end as panelist_online,
        P.badgeid
    FROM
                  Sessions S 
             JOIN Tracks T USING (trackid)
             JOIN Types Ty USING (typeid)
        LEFT JOIN ParticipantSessionInterest PSI USING (sessionid)
        LEFT JOIN Participants P ON PSI.badgeid = P.badgeid AND P.interested = 1
    WHERE
        S.statusid IN (2,3,7)
        AND S.divisionid in (select divisionid from Divisions where divisionname = 'Panels')) FB
GROUP BY
    sessionid, title, trackname, display_order
ORDER BY
    display_order, sessionid;
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
                            <th rowspan="2">Track</th>
                            <th rowspan="2">Session ID</th>
                            <th rowspan="2">Title</th>
                            <th rowspan="2">Rank</th>
                            <th colspan="5">Will attend</th>
                            <th colspan="3">How attend</th>
                            <th colspan="5">Assigned</th>
                            <th rowspan="2">Online Only</th>
                            <th rowspan="2">Will Mod</th>
                        </tr>
                        <tr>
                            <th>Very likely</th>
                            <th>Likely</th>
                            <th>Maybe</th>
                            <th>Unlikely</th>
                            <th>Very unlikely</th>
                            <th>In-person</th>
                            <th>Online</th>
                            <th>Either</th>
                            <th>Very yes</th>
                            <th>Yes</th>
                            <th>Maybe</th>
                            <th>Last resort</th>
                            <th>No</th>
                        </tr>
                    </thead>
                    <tbody>
                        <xsl:apply-templates select="doc/query[@queryName='sessions']/row"/>
                    </tbody>
                </table>
            </xsl:when>
            <xsl:otherwise>
                <div class="text-info">No results found.</div>
            </xsl:otherwise>                    
        </xsl:choose>
    </xsl:template>

    <xsl:template match="doc/query[@queryName='sessions']/row">
        <tr>
            <td><xsl:value-of select="@trackname" /></td>
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
            <td><xsl:value-of select="@attendPos" /></td>
            <td><xsl:value-of select="@attend1" /></td>
            <td><xsl:value-of select="@attend2" /></td>
            <td><xsl:value-of select="@attend3" /></td>
            <td><xsl:value-of select="@attend4" /></td>
            <td><xsl:value-of select="@attend5" /></td>
            <td><xsl:value-of select="@attend_type1" /></td>
            <td><xsl:value-of select="@attend_type2" /></td>
            <td><xsl:value-of select="@attend_type3" /></td>
            <td><xsl:value-of select="@rank1" /></td>
            <td><xsl:value-of select="@rank2" /></td>
            <td><xsl:value-of select="@rank3" /></td>
            <td><xsl:value-of select="@rank4" /></td>
            <td><xsl:value-of select="@rank5" /></td>
            <td><xsl:value-of select="@panelist_online" /></td>
            <td><xsl:value-of select="@willmoderate1" /></td>
        </tr>
    </xsl:template>
</xsl:stylesheet>
EOD;
