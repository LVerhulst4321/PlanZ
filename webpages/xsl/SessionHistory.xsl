<?xml version="1.0" encoding="UTF-8" ?>
<!--
    Created by Peter Olszowka on 2016-05-11;
    Copyright (c) 2011-2019 Peter Olszowka. All rights reserved. See copyright document for more details.
-->
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
    <xsl:output encoding="UTF-8" indent="yes" method="html" />
    <xsl:variable name="selsessionid" select="/doc/parameters/@selsessionid" />
    <xsl:template match="/">
        <div class="container">
            <div class="card">
                <div class="card-body">
                    <form id="session-history-form" name="selsesform" method="get" action="SessionHistory.php">
                        <div class="row align-items-center">
                            <div class="col-md-2">
                                <label for="sessionDropdown" class="mb-0">Select Session:</label>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-0">
                                    <select id="sessionDropdown" class="form-control" name="selsess">
                                        <option value="0">
                                            <xsl:if test="$selsessionid = '0'">
                                                <xsl:attribute name="selected">selected</xsl:attribute>
                                            </xsl:if>
                                            <xsl:text>Select Session</xsl:text>
                                        </option>
                                        <xsl:apply-templates select="doc/query[@queryName='chooseSession']/row" >
                                            <xsl:sort select="@trackname" />
                                            <xsl:sort select="@sessionid" data-type="number" />
                                        </xsl:apply-templates>
                                    </select>
                                </div>
                            </div>
                            <div className="col-md-2">
                                <button id="sessionBtn" type="submit" name="submit" class="btn btn-primary">
                                    <xsl:if test="$selsessionid = '0'">
                                        <xsl:attribute name="disabled">disabled</xsl:attribute>
                                    </xsl:if>
                                    <xsl:text>Select Session</xsl:text>
                                </button>
                            </div>
                        </div>
                    </form>
                    <hr />
                    <xsl:if test="$selsessionid != '0'">
                        <h2>
                            <xsl:value-of select="doc/query[@queryName='title']/row/@title" />
                        </h2>
                        <h4>Current Participants</h4>
                        <xsl:choose>
                            <xsl:when test="count(doc/query[@queryName='currentAssignments']/row) > 0">
                                <xsl:apply-templates select="doc/query[@queryName='currentAssignments']/row" />
                            </xsl:when>
                            <xsl:otherwise>
                                <p>No participants are currently assigned.</p>
                            </xsl:otherwise>
                        </xsl:choose>
                        <h4 class="mt-3">Edits</h4>
                        <table class="table table-sm">
                            <tbody>
                                <xsl:apply-templates select="doc/query[@queryName='timestamps']/row" />
                            </tbody>
                        </table>
                    </xsl:if>
                </div>
            </div>
        </div>
    </xsl:template>

    <xsl:template match="doc/query[@queryName='chooseSession']/row">
        <option value="{@sessionid}">
            <xsl:if test="@sessionid = $selsessionid"><xsl:attribute name="selected">selected</xsl:attribute></xsl:if>
            <xsl:value-of select="@trackname"/> - <xsl:value-of select="@sessionid" /> - <xsl:value-of select="@title" />
        </option>
    </xsl:template>

    <xsl:template match="doc/query[@queryName='currentAssignments']/row">
        <div class="row-fluid">
            <span class="span11 offset1">
                <xsl:value-of select="@pubsname"/> (<xsl:value-of select="@badgeid" />) <xsl:if test="@moderator='1'"><span class="za-sessionHistory-moderator">moderator</span></xsl:if>
            </span>
        </div>
    </xsl:template>

    <xsl:template match="doc/query[@queryName='timestamps']/row">
        <xsl:variable name="timestamp" select="@timestamp" />
        <xsl:variable name="createParticipantRow" select="/doc/query[@queryName='participantedits']/row[@createdts = $timestamp][1]" />
        <xsl:variable name="inactivateParticipantRow" select="/doc/query[@queryName='participantedits']/row[@inactivatedts = $timestamp][1]" />
        <xsl:variable name="editSessionRow" select="/doc/query[@queryName='sessionedits']/row[@timestamp = $timestamp][1]" />
        <xsl:if test="count($createParticipantRow) > 0 or count($inactivateParticipantRow) > 0 or count($editSessionRow) > 0">
            <tr>
                <td>
                    <xsl:choose>
                        <xsl:when test="count($createParticipantRow) > 0">
                            <xsl:value-of select="$createParticipantRow/@crpubsname"/> (<xsl:value-of select="$createParticipantRow/@createdbybadgeid" />)
                        </xsl:when>
                        <xsl:when test="count($inactivateParticipantRow) > 0">
                            <xsl:value-of select="$inactivateParticipantRow/@inactpubsname"/> (<xsl:value-of select="$inactivateParticipantRow/@inactivatedbybadgeid" />)
                        </xsl:when>
                        <xsl:when test="count($editSessionRow) > 0">
                            <xsl:value-of select="$editSessionRow/@name"/> (<xsl:value-of select="$editSessionRow/@fullname" /> - <xsl:value-of select="$editSessionRow/@badgeid" />)
                        </xsl:when>
                    </xsl:choose>
                </td>
                <td>
                    <xsl:choose>
                        <xsl:when test="count($createParticipantRow) > 0">
                            <xsl:value-of select="$createParticipantRow/@createdtsformat" />
                        </xsl:when>
                        <xsl:when test="count($inactivateParticipantRow) > 0">
                            <xsl:value-of select="$inactivateParticipantRow/@inactivatedtsformat" />
                        </xsl:when>
                        <xsl:when test="count($editSessionRow) > 0">
                            <xsl:value-of select="$editSessionRow/@tsformat" />
                        </xsl:when>
                    </xsl:choose>
                </td>
                <xsl:call-template name="processModeratorEdit">
                    <xsl:with-param name="timestamp" select = "$timestamp" />
                </xsl:call-template>
                <xsl:apply-templates mode="additions" select="/doc/query[@queryName='participantedits']/row[@createdts = $timestamp]" />
                <xsl:apply-templates mode="deletions" select="/doc/query[@queryName='participantedits']/row[@inactivatedts = $timestamp]" />
                <xsl:apply-templates select="/doc/query[@queryName='sessionedits']/row[@timestamp = $timestamp]" />
            </tr>
        </xsl:if>
    </xsl:template>

    <xsl:template name="processModeratorEdit">
        <xsl:param name="timestamp" />
        <xsl:variable name="addModeratorRow" select="/doc/query[@queryName='participantedits']/row[@createdts = $timestamp and @moderator='1']" />
        <xsl:variable name="deleteModeratorRow" select="/doc/query[@queryName='participantedits']/row[@inactivatedts = $timestamp and @moderator='1']" />
        <xsl:if test="count($addModeratorRow) > 0 or count($deleteModeratorRow) > 0">
            <td>
                <xsl:choose>
                    <xsl:when test="count($addModeratorRow) > 0 and count($deleteModeratorRow) > 0">
                        Change moderator from <xsl:value-of select="$deleteModeratorRow/@pubsname"/> (<xsl:value-of select="$deleteModeratorRow/@badgeid" />)
                        to <xsl:value-of select="$addModeratorRow/@pubsname"/> (<xsl:value-of select="$addModeratorRow/@badgeid" />).
                    </xsl:when>
                    <xsl:when test="count($addModeratorRow) > 0">
                        Assign <xsl:value-of select="$addModeratorRow/@pubsname"/> (<xsl:value-of select="$addModeratorRow/@badgeid" />) as moderator.
                    </xsl:when>
                    <xsl:otherwise>
                        Remove <xsl:value-of select="$deleteModeratorRow/@pubsname"/> (<xsl:value-of select="$deleteModeratorRow/@badgeid" />) from moderator.
                    </xsl:otherwise>
                </xsl:choose>
            </td>
        </xsl:if>
    </xsl:template>

    <xsl:template mode="additions" match="doc/query[@queryName='participantedits']/row">
        <xsl:variable name="timestamp" select="@createdts" />
        <xsl:variable name="badgeid" select="@badgeid" />
        <xsl:if test="count(/doc/query[@queryName='participantedits']/row[@inactivatedts = $timestamp and @badgeid = $badgeid]) = 0">
            <td>
                <span>Add <xsl:value-of select="@pubsname"/> (<xsl:value-of select="$badgeid" />) to panel.</span>
            </td>
        </xsl:if>
    </xsl:template>

    <xsl:template mode="deletions" match="doc/query[@queryName='participantedits']/row">
        <xsl:variable name="timestamp" select="@inactivatedts" />
        <xsl:variable name="badgeid" select="@badgeid" />
        <xsl:if test="count(/doc/query[@queryName='participantedits']/row[@createdts = $timestamp and @badgeid = $badgeid]) = 0">
            <td>
                Remove <xsl:value-of select="@pubsname"/> (<xsl:value-of select="$badgeid" />) from panel.
            </td>
        </xsl:if>
    </xsl:template>

    <xsl:template match="doc/query[@queryName='sessionedits']/row">
        <xsl:variable name="timestamp" select="@timestamp" />
        <xsl:variable name="badgeid" select="@badgeid" />
        <td>
            <xsl:value-of select="@codedescription" /> —
            <xsl:if test="@editdescription"><xsl:value-of select="@editdescription" /> — </xsl:if>
            status:<xsl:value-of select="@statusname" />
        </td>
    </xsl:template>
</xsl:stylesheet>
