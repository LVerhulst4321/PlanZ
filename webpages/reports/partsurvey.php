<?php
// Copyright (c) 2018-2019 Peter Olszowka. All rights reserved. See copyright document for more details.
$report = [];
$report['name'] = 'Participant Survey Submissions';
$report['multi'] = 'true';
$report['output_filename'] = 'partsurvey.csv';
$report['description'] = 'Show all participants who submitted survey answers.';
$report['categories'] = array(
    'Participant Info Reports' => 10,
);
$report['columns'] = array(
    array("width" => "6em"),
    array("width" => "12em", "orderData" => 2),
    array("width" => "24em"),
    array("width" => "24em"),
);
$report['queries'] = [];
$report['queries']['survey'] =<<<'EOD'
SELECT
        P.badgeid,
        P.pubsname,
        A17.value AS availability,
        A19.value AS day_job,
        A20.value AS age_range,
        A21.value AS race_ethnicity,
        A22.value AS accessibility,
        A23.value AS gender,
        A24.value AS sexual_orientation,
        A25.value AS pronouns,
        MAX(A.lastupdate) AS lastupdate
    FROM
        Participants P
        INNER JOIN ParticipantSurveyAnswers A ON P.badgeid = A.participantid
        LEFT JOIN ParticipantSurveyAnswers A17 ON P.badgeid = A17.participantid AND A17.questionid = 17
        LEFT JOIN ParticipantSurveyAnswers A19 ON P.badgeid = A19.participantid AND A19.questionid = 19
        LEFT JOIN ParticipantSurveyAnswers A20 ON P.badgeid = A20.participantid AND A20.questionid = 20
        LEFT JOIN ParticipantSurveyAnswers A21 ON P.badgeid = A21.participantid AND A21.questionid = 21
        LEFT JOIN ParticipantSurveyAnswers A22 ON P.badgeid = A22.participantid AND A22.questionid = 22
        LEFT JOIN ParticipantSurveyAnswers A23 ON P.badgeid = A23.participantid AND A23.questionid = 23
        LEFT JOIN ParticipantSurveyAnswers A24 ON P.badgeid = A24.participantid AND A24.questionid = 24
        LEFT JOIN ParticipantSurveyAnswers A25 ON P.badgeid = A25.participantid AND A25.questionid = 25
    GROUP BY
        P.badgeid
    ORDER BY
        P.sortedpubsname;
EOD;
$report['xsl'] =<<<'EOD'
<?xml version="1.0" encoding="UTF-8" ?>
<xsl:stylesheet version="1.1" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
    <xsl:output encoding="UTF-8" indent="yes" method="html" />
    <xsl:include href="xsl/reportInclude.xsl" />
    <xsl:template match="/">
        <xsl:choose>
            <xsl:when test="doc/query[@queryName='survey']/row">
                <table id="reportTable" class="table table-sm table-bordered">
                    <thead>
                        <tr style="height:2.6rem">
                            <th>Person Id</th>
                            <th>Participant Name</th>
                            <th>Availability</th>
                            <th>Day Job</th>
                            <th>Age Range</th>
                            <th>Race/Ethnicity</th>
                            <th>Accessibility</th>
                            <th>Gender</th>
                            <th>Sexual Orientation</th>
                            <th>Pronouns</th>
                            <th>Last Updated</th>
                        </tr>
                    </thead>
                    <xsl:apply-templates select="doc/query[@queryName='survey']/row" />
                </table>
            </xsl:when>
            <xsl:otherwise>
                <div class="alert alert-danger">No results found.</div>
            </xsl:otherwise>
        </xsl:choose>
    </xsl:template>
    <xsl:template match="doc/query[@queryName='survey']/row">
        <tr>
            <td><xsl:call-template name="showBadgeid"><xsl:with-param name="badgeid" select="@badgeid"/></xsl:call-template></td>
            <td><xsl:value-of select="@pubsname" /></td>
            <td><xsl:value-of select="@availability" /></td>
            <td><xsl:value-of select="@day_job" /></td>
            <td><xsl:value-of select="@age_range" /></td>
            <td><xsl:value-of select="@race_ethnicity" /></td>
            <td><xsl:value-of select="@accessibility" /></td>
            <td><xsl:value-of select="@gender" /></td>
            <td><xsl:value-of select="@sexual_orientation" /></td>
            <td><xsl:value-of select="@pronouns" /></td>
            <td><xsl:value-of select="@lastupdate" /></td>
        </tr>
    </xsl:template>
</xsl:stylesheet>
EOD;