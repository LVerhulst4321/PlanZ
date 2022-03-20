<?php
// Copyright (c) 2018-2021 Peter Olszowka. All rights reserved. See copyright document for more details.
$report = [];
$report['name'] = 'Staff Members';
$report['multi'] = 'true';
$report['output_filename'] = 'staff_members.csv';
$report['description'] = 'List Staff Members and their priviliges';
$report['categories'] = array(
    'Administration Reports' => 1010,
    'Security Reports' => 30,
);
$report['columns'] = array(
    null,
    array("orderData" => 2),
    array("visible" => false),
    array("orderData" => 4),
    array("visible" => false),
    null,
    null,
    array("orderable" => false)
);
$report['queries'] = [];
if (empty(DEFAULT_USER_PASSWORD)) {
    $report['queries']['bad_password'] = "SELECT badgeid FROM Participants WHERE 1 = 2;";
    $defaultUserPassword = '';
} else {
    $defaultUserPassword = DEFAULT_USER_PASSWORD;
    // Have to run a query here to find all the default passwords;
    $badPasswordArr = array();
    $prequery =<<<EOD
SELECT
        P.badgeid, P.password
    FROM
         Participants P
    WHERE EXISTS (SELECT *
        FROM
            UserHasPermissionRole UHPR
        WHERE
                UHPR.badgeid = P.badgeid
            AND UHPR.permroleid IN (1, 2, 12)) /* admin, staff, senior staff */;
EOD;
    if (!$result = mysqli_query_exit_on_error($prequery)) {
        exit(0); //should have exited already
    }
    while ($resultObj = mysqli_fetch_object($result)) {
        if (password_verify(DEFAULT_USER_PASSWORD, $resultObj->password)) {
            $badPasswordArr[] = "'{$resultObj->badgeid}'";
        }
    }
    if (count($badPasswordArr) > 0) {
        $badPasswordList = implode(',', $badPasswordArr);
        $report['queries']['bad_password'] = "SELECT badgeid FROM Participants WHERE badgeid IN ($badPasswordList);";
    } else {
        $report['queries']['bad_password'] = "SELECT badgeid FROM Participants WHERE 0 = 1;";
    }
}

$report['queries']['staff'] =<<<EOD
SELECT
        DISTINCT badgeid,
        P.pubsname,
        CONCAT(CD.firstname,' ',CD.lastname) AS name,
        CONCAT(CD.lastname, CD.firstname) AS nameSort,
        P.sortedpubsname,
        CD.email AS email
    FROM
             Participants P
        JOIN CongoDump CD USING (badgeid)
    WHERE EXISTS (SELECT *
        FROM
            UserHasPermissionRole UHPR
        WHERE
                UHPR.badgeid = P.badgeid
            AND UHPR.permroleid IN (1, 2, 12)) /* admin, staff, senior staff */
    ORDER BY
        nameSort;
EOD;
$report['queries']['privileges'] =<<<'EOD'
SELECT
        UHPR.badgeid,
        PR.permrolename
    FROM
             UserHasPermissionRole UHPR
        JOIN PermissionRoles PR USING (permroleid)
    WHERE EXISTS (SELECT *
        FROM
            UserHasPermissionRole UHPR2
        WHERE
                UHPR2.badgeid = UHPR.badgeid
            AND UHPR.permroleid IN (1, 2, 12)) /* admin, staff, senior staff */;
EOD;
$report['xsl'] =<<<EOD
<?xml version="1.0" encoding="UTF-8" ?>
<xsl:stylesheet version="1.1" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
    <xsl:output encoding="UTF-8" indent="yes" method="html" />
    <xsl:include href="xsl/reportInclude.xsl" />
    <xsl:template match="/">
        <xsl:choose>
            <xsl:when test="doc/query[@queryName='staff']/row">
                <table id="reportTable" class="table table-sm table-bordered">
                    <thead>
                         <tr style="height:2.6rem">
                            <th>Person ID</th>
                            <th>Name</th>
                            <th></th>
                            <th>Name for publications</th>
                            <th></th>
                            <th>Password</th>
                            <th>Email</th>
                            <th>Permission roles</th>
                        </tr>
                    </thead>
                    <tbody>
                        <xsl:apply-templates select="doc/query[@queryName='staff']/row"/>
                    </tbody>
                </table>
            </xsl:when>
            <xsl:otherwise>
                <div class="alert alert-danger">No results found.</div>
            </xsl:otherwise>
        </xsl:choose>
    </xsl:template>

    <xsl:template match="doc/query[@queryName='staff']/row">
        <xsl:variable name="badgeid" select="@badgeid" />
        <tr>
            <td><xsl:call-template name="showBadgeid"><xsl:with-param name="badgeid" select="@badgeid"/></xsl:call-template></td>
            <td><xsl:value-of select="@name"/></td>
            <td><xsl:value-of select="@nameSort"/></td>
            <td><xsl:value-of select="@pubsname"/></td>
            <td><xsl:value-of select="@sortedpubsname"/></td>
            <td>
                <xsl:choose>
                    <xsl:when test="/doc/query[@queryName='bad_password']/row[@badgeid=\$badgeid]">
                        <xsl:text>$defaultUserPassword</xsl:text>
                    </xsl:when>
                    <xsl:otherwise>
                        <xsl:text>OK</xsl:text>
                    </xsl:otherwise>
                </xsl:choose>
            </td>
            <td><xsl:value-of select="@email"/></td>
            <td>
                <xsl:apply-templates select="/doc/query[@queryName = 'privileges']/row[@badgeid = \$badgeid]"/>
            </td>
        </tr>
    </xsl:template>

    <xsl:template match="/doc/query[@queryName='privileges']/row">
        <div><xsl:value-of select="@permrolename"/></div>
    </xsl:template>
</xsl:stylesheet>
EOD;
