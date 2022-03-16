<?php
// Copyright (c) 2018-2019 Peter Olszowka. All rights reserved. See copyright document for more details.
$report = [];
$report['multi'] = 'true';
$report['output_filename'] = 'participantroles.csv';
$report['name'] = 'Participant Roles';
$report['description'] = 'What Roles is a participant willing to take?';
$report['categories'] = array(
    'Participant Info Reports' => 740,
);
$report['columns'] = array(
    array("width" => "6em"),
    array("width" => "12em"),
    array("width" => "12em", "orderable" => false),
    array("orderable" => false)
);
$report['queries'] = [];
$report['queries']['participants'] =<<<'EOD'
SELECT
        P.badgeid,
        P.pubsname,
        PI.otherroles,
        CD.badgename,
        concat(CD.firstname,' ',CD.lastname) AS name,
        IF(instr(P.pubsname, CD.lastname) > 0, CD.lastname, substring_index(P.pubsname, ' ', -1)) AS pubsnameSort
    FROM
                  Participants P
             JOIN CongoDump CD USING (badgeid)
        LEFT JOIN ParticipantInterests PI USING (badgeid)
    WHERE
        P.interested = 1 /* Interested */
    ORDER BY
        IF(instr(P.pubsname, CD.lastname) > 0, CD.lastname, substring_index(P.pubsname, ' ', -1)), CD.firstname;
EOD;
$report['queries']['roles'] =<<<'EOD'
SELECT
        P.badgeid,
        R.rolename
    FROM
             Participants P
        JOIN ParticipantHasRole PHR USING (badgeid)
        JOIN Roles R USING (roleid)
    WHERE
        P.interested = 1 /* Interested */;
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
                        <tr style="height:2.6rem">
                            <th>Person ID</th>
                            <th>Name for Publications</th>
                            <th>Roles</th>
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
        <xsl:variable name="badgeid" select="@badgeid" />
        <tr>
            <td>
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
            <td>
                <xsl:apply-templates select="/doc/query[@queryName='roles']/row[@badgeid=$badgeid]" />
            </td>
            <td><xsl:value-of select="@otherroles"/></td>
        </tr>
    </xsl:template>
    
    <xsl:template match="doc/query[@queryName='roles']/row">
        <div>
            <xsl:value-of select="@rolename" />
        </div>
    </xsl:template>
</xsl:stylesheet>
EOD;
