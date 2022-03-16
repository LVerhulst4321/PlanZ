<?php
// Copyright (c) 2018-2019 Peter Olszowka. All rights reserved. See copyright document for more details.
$report = [];
$report['multi'] = 'true';
$report['output_filename'] = 'participantinterests.csv';
$report['name'] = 'Participant General Interests';
$report['description'] = 'What is that participant interested in? (Program Participants who are attending)';
$report['categories'] = array(
    'Participant Info Reports' => 720,
    'Programming Reports' => 1,
);
$report['columns'] = array(
    null,
    array("orderData" => 2),
    array("visible" => false),
    array("orderable" => false),
    array("orderable" => false),
    array("orderable" => false),
    array("orderable" => false),
    array("orderable" => false)
);
$report['queries'] = [];
$report['queries']['participants'] =<<<'EOD'
SELECT
        P.badgeid,
        P.pubsname,
        PI.yespanels,
        PI.nopanels,
        PI.yespeople,
        PI.nopeople,
        PI.otherroles,
        CD.badgename,
        concat(CD.firstname,' ',CD.lastname) AS name,
        IF(instr(P.pubsname, CD.lastname) > 0, CD.lastname, substring_index(P.pubsname, ' ', -1)) AS pubsnameSort
    FROM
             Participants P
        JOIN ParticipantInterests PI USING (badgeid)
        JOIN CongoDump CD USING (badgeid)
        JOIN UserHasPermissionRole UHPR USING (badgeid)
    WHERE
            P.interested = 1
        AND UHPR.permroleid = 3 /* Program Participant */
    ORDER BY
        IF(instr(P.pubsname, CD.lastname) > 0, CD.lastname, substring_index(P.pubsname, ' ', -1)), CD.firstname;
EOD;
$report['xsl'] =<<<'EOD'
<?xml version="1.0" encoding="UTF-8" ?>
<xsl:stylesheet version="1.1" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
    <xsl:output encoding="UTF-8" indent="yes" method="html" />
    <xsl:include href="xsl/reportInclude.xsl" />
    <xsl:template match="/">
        <xsl:choose>
            <xsl:when test="doc/query[@queryName='participants']/row">
                <table id="reportTable" class="table table-sm table-bordered">
                    <thead>
                        <tr>
                            <th style="white-space: nowrap;">Person ID</th>
                            <th style="white-space: nowrap;">Name for Publications</th>
                            <th></th>
                            <th>"Workshops or presentations I'd like to run"</th>
                            <th>"Panel types I am not interested in participating in"</th>
                            <th>"People with whom I'd like to be on a session"</th>
                            <th>"People with whom I'd rather not be on a session"</th>
                            <th>"Other" Role Details</th>
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
        <xsl:variable name="bagdeid" select="@badgeid" />
        <tr>
            <td style="white-space: nowrap;">
                <xsl:call-template name="showBadgeid">
                    <xsl:with-param name="badgeid" select = "@badgeid" />
                </xsl:call-template>
            </td>
            <td>
                <xsl:call-template name="showLinkedPubsname">
                    <xsl:with-param name="badgeid" select = "@badgeid" />
                    <xsl:with-param name="pubsname" select = "@pubsname" />
                    <xsl:with-param name="badgename" select = "@badgename" />
                    <xsl:with-param name="name" select = "@name" />
                </xsl:call-template>
            </td>
            <td><xsl:value-of select="@pubsnameSort"/></td>
            <td><xsl:value-of select="@yespanels"/></td>
            <td><xsl:value-of select="@nopanels"/></td>
            <td><xsl:value-of select="@yespeople"/></td>
            <td><xsl:value-of select="@nopeople"/></td>
            <td><xsl:value-of select="@otherroles"/></td>
        </tr>
    </xsl:template>
</xsl:stylesheet>
EOD;
