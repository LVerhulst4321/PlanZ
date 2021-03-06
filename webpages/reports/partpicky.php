<?php
// Copyright (c) 2018-2019 Peter Olszowka. All rights reserved. See copyright document for more details.
$report = [];
$report['name'] = 'Participants With People to Avoid';
$report['multi'] = 'true';
$report['output_filename'] = 'partipants_with_avoid_list.csv';
$report['description'] = 'Show the id, pubsname and list of people to avoid for each participant who indicated he is attending and listed people with whom he does not want to share a panel.';
$report['categories'] = array(
    'Participant Info Reports' => 760,
    'Programming Reports' => 1,
);
$report['columns'] = array(
    array("width" => "5em"),
    array("width" => "12em", "orderData" => 2),
    array("visible" => false),
    array("orderable" => false)
);
$report['queries'] = [];
$report['queries']['participants'] =<<<'EOD'
SELECT
        PI.badgeid,
        P.pubsname,
        PI.nopeople,
        CD.badgename,
        concat(CD.firstname,' ',CD.lastname) AS name,
        IF(instr(P.pubsname,CD.lastname) > 0, CD.lastname, substring_index(P.pubsname, ' ', -1)) AS pubsnameSort
    FROM
             ParticipantInterests PI
        JOIN Participants P USING (badgeid)
        JOIN CongoDump CD USING (badgeid)
    WHERE
            P.interested = 1 
        AND (PI.nopeople IS NOT NULL AND PI.nopeople != '')
    ORDER BY
        IF(instr(P.pubsname,CD.lastname) > 0, CD.lastname, substring_index(P.pubsname, ' ', -1)),
        CD.firstname;
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
                            <th>Person ID</th>
                            <th>Pubsname</th>
                            <th></th>
                            <th>Other participants to avoid</th>
                        </tr>
                    </thead>
                    <tbody>
                        <xsl:apply-templates select="/doc/query[@queryName='participants']/row"/>
                    </tbody>
                </table>
            </xsl:when>
            <xsl:otherwise>
                <div class="alert alert-danger">No results found.</div>
            </xsl:otherwise>
        </xsl:choose>
    </xsl:template>
    <xsl:template match="/doc/query[@queryName='participants']/row">
        <tr>
            <td><xsl:value-of select="@badgeid" /></td>
            <td>
                <xsl:call-template name="showLinkedPubsname">
                    <xsl:with-param name="badgeid" select = "@badgeid" />
                    <xsl:with-param name="pubsname" select = "@pubsname" />
                    <xsl:with-param name="badgename" select = "@badgename" />
                    <xsl:with-param name="name" select = "@name" />
                </xsl:call-template>
            </td>
            <td><xsl:value-of select="@pubsnameSort" /></td>
            <td><xsl:value-of select="@nopeople" /></td>
        </tr>
    </xsl:template>
</xsl:stylesheet>
EOD;
