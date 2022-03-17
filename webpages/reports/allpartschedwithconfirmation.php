<?php
$report = [];
$report['name'] = 'Participant Assignments with Confirmation Status';
$report['multi'] = 'true';
$report['output_filename'] = 'participant_assignment_confirmation_status.csv';
$report['description'] = 'The schedule sorted by participant, then time limited to program participants';
$report['categories'] = array(
    'Programming Reports' => 20,
    'GOH Reports' => 20,
);
$report['queries'] = [];
$report['queries']['participants'] =<<<'EOD'
SELECT DISTINCT
        P.badgeid, P.pubsname, C.firstname, C.lastname
    FROM
             Participants P
        JOIN CongoDump C USING (badgeid)
        JOIN ParticipantOnSession POS USING (badgeid)
        JOIN Sessions S USING (sessionid)
        JOIN Schedule SCH USING (sessionid)
        JOIN UserHasPermissionRole UHPR USING (badgeid)
    WHERE
        UHPR.permroleid = 3 /* Program Participant */
    ORDER BY
        IF(instr(P.pubsname,C.lastname)>0,C.lastname,substring_index(P.pubsname,' ',-1)),
        C.firstname;
EOD;
$report['queries']['schedule'] =<<<'EOD'
SELECT
        P.pubsname, P.badgeid, POS.moderator, S.duration, R.roomname, R.function, TR.trackname, 
        C.badgename, concat(C.firstname,' ',C.lastname) AS name,
        S.sessionid, S.title, DATE_FORMAT(ADDTIME('$ConStartDatim$',SCH.starttime),'%a %l:%i %p') AS starttime,
        POS.confirmed, POS.notes
    FROM
             Participants P
        JOIN CongoDump C USING (badgeid)
        JOIN ParticipantOnSession POS USING (badgeid)
        JOIN Sessions S USING (sessionid)
        JOIN Schedule SCH USING (sessionid)
        JOIN Rooms R USING (roomid)
        JOIN Tracks TR USING (trackid)
    ORDER BY
        IF(instr(P.pubsname,C.lastname)>0,C.lastname,substring_index(P.pubsname,' ',-1)),
        C.firstname,
	SCH.starttime;
EOD;
$report['xsl'] =<<<'EOD'
<?xml version="1.0" encoding="UTF-8" ?>
<xsl:stylesheet version="1.1" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
    <xsl:output encoding="UTF-8" indent="yes" method="html" />
    <xsl:include href="xsl/reportInclude.xsl" />
    <xsl:template match="/">
        <xsl:choose>
            <xsl:when test="doc/query[@queryName='participants']/row">
                <table class="table table-sm table-bordered">
                    <thead>
                        <tr>
                            <th>Pubsname</th>
                            <th>Title</th>
                            <th>Moderator ?</th>
                            <th>Room Name</th>
                            <th>Start Time</th>
                            <th>Confirm</th>
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <xsl:for-each select="/doc/query[@queryName='participants']/row">
                            <xsl:variable name="badgeid"><xsl:value-of select="@badgeid" /></xsl:variable>
                            <xsl:call-template name="usersSchedule">
                                <xsl:with-param name="badgeid" select = "@badgeid" />
                                <xsl:with-param name="rowdata" select = "/doc/query[@queryName='schedule']/row[@badgeid = $badgeid]" />
                            </xsl:call-template>
                        </xsl:for-each>
                    </tbody>
                </table>
            </xsl:when>
            <xsl:otherwise>
                <div class="alert alert-danger">No results found.</div>
            </xsl:otherwise>                    
        </xsl:choose>
    </xsl:template>
    
    <xsl:template name="usersSchedule">
        <xsl:param name="badgeid" />
	    <xsl:param name="rowdata" />
	    <xsl:for-each select="$rowdata">
            <tr>
                <xsl:choose>
                    <xsl:when test="position() = 1">
                        <td rowspan="{last()}" style="border-top-width:2px">
                            <xsl:call-template name="showLinkedPubsname">
                                <xsl:with-param name="badgeid" select = "@badgeid" />
                                <xsl:with-param name="pubsname" select = "@pubsname" />
                                <xsl:with-param name="badgename" select = "@badgename" />
                                <xsl:with-param name="name" select = "@name" />
                            </xsl:call-template>
                        </td>
                        <td style="border-top-width:2px">
                            <xsl:call-template name="showSessionTitle">
                                <xsl:with-param name="sessionid" select = "@sessionid" />
                                <xsl:with-param name="title" select = "@title" />
                            </xsl:call-template>
                        </td>
                        <td style="border-top-width:2px">
                            <xsl:if test="@moderator='1'">Yes</xsl:if>
                            <xsl:text disable-output-escaping="yes">&amp;nbsp;</xsl:text>
                        </td>
                        <td style="border-top-width:2px">
                            <xsl:call-template name="showRoomName">
                                <xsl:with-param name="roomid" select = "@roomid" />
                                <xsl:with-param name="roomname" select = "@roomname" />
                            </xsl:call-template>
                        </td>
                        <td style="border-top-width:2px">
                            <xsl:value-of select="@starttime" />
                        </td>
                        <td style="border-top-width:2px">
                            <xsl:choose>
                                <xsl:when test="@confirmed = 'ACCEPTED'">
                                    <xsl:text>Accepted</xsl:text>
                                </xsl:when>
                                <xsl:when test="@confirmed = 'DECLINED'">
                                    <xsl:text>Declined</xsl:text>
                                </xsl:when>
                                <xsl:when test="@confirmed = 'MAYBE'">
                                    <xsl:text>Maybe</xsl:text>
                                </xsl:when>
                                <xsl:otherwise>
                                    <xsl:text>Unconfirmed</xsl:text>
                                </xsl:otherwise>
                            </xsl:choose>
                        </td>
                        <td style="border-top-width:2px">
                            <xsl:value-of select="@notes" />
                        </td>
                    </xsl:when>
                    <xsl:when test="position() = last()">
                        <td style="border-bottom:2px">
                            <xsl:call-template name="showSessionTitle">
                                <xsl:with-param name="sessionid" select = "@sessionid" />
                                <xsl:with-param name="title" select = "@title" />
                            </xsl:call-template>
                        </td>
                        <td style="border-bottom:2px">
                            <xsl:if test="@moderator='1'">Yes</xsl:if>
                            <xsl:text disable-output-escaping="yes">&amp;nbsp;</xsl:text>
                        </td>
                        <td style="border-bottom:2px">
                            <xsl:call-template name="showRoomName">
                                <xsl:with-param name="roomid" select = "@roomid" />
                                <xsl:with-param name="roomname" select = "@roomname" />
                            </xsl:call-template>
                        </td>
                        <td style="border-bottom:2px">
                            <xsl:value-of select="@starttime" />
                        </td>
                        <td style="border-top-width:2px">
                            <xsl:choose>
                                <xsl:when test="@confirmed = 'ACCEPTED'">
                                    <xsl:text>Accepted</xsl:text>
                                </xsl:when>
                                <xsl:when test="@confirmed = 'DECLINED'">
                                    <xsl:text>Declined</xsl:text>
                                </xsl:when>
                                <xsl:when test="@confirmed = 'MAYBE'">
                                    <xsl:text>Maybe</xsl:text>
                                </xsl:when>
                                <xsl:otherwise>
                                    <xsl:text>Unconfirmed</xsl:text>
                                </xsl:otherwise>
                            </xsl:choose>
                        </td>
                        <td style="border-top-width:2px">
                            <xsl:value-of select="@notes" />
                        </td>
                    </xsl:when>
                    <xsl:otherwise>
                        <td>
                            <xsl:call-template name="showSessionTitle">
                                <xsl:with-param name="sessionid" select = "@sessionid" />
                                <xsl:with-param name="title" select = "@title" />
                            </xsl:call-template>
                        </td>
                        <td>
                            <xsl:if test="@moderator='1'">Yes</xsl:if>
                            <xsl:text disable-output-escaping="yes">&amp;nbsp;</xsl:text>
                        </td>
                        <td>
                            <xsl:call-template name="showRoomName">
                                <xsl:with-param name="roomid" select = "@roomid" />
                                <xsl:with-param name="roomname" select = "@roomname" />
                            </xsl:call-template>
                        </td>
                        <td>
                            <xsl:value-of select="@starttime" />
                        </td>
                        <td>
                            <xsl:choose>
                                <xsl:when test="@confirmed = 'ACCEPTED'">
                                    <xsl:text>Accepted</xsl:text>
                                </xsl:when>
                                <xsl:when test="@confirmed = 'DECLINED'">
                                    <xsl:text>Declined</xsl:text>
                                </xsl:when>
                                <xsl:when test="@confirmed = 'MAYBE'">
                                    <xsl:text>Maybe</xsl:text>
                                </xsl:when>
                                <xsl:otherwise>
                                    <xsl:text>Unconfirmed</xsl:text>
                                </xsl:otherwise>
                            </xsl:choose>
                        </td>
                        <td>
                            <xsl:value-of select="@notes" />
                        </td>
                    </xsl:otherwise>
                </xsl:choose>
            </tr>
        </xsl:for-each>
    </xsl:template>
</xsl:stylesheet>
EOD;
