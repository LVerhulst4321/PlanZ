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
                                <xsl:apply-templates select="doc/query[@queryName='changes']/row" />
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

    <xsl:template match="doc/query[@queryName='changes']/row">
        <tr>
            <td>
                <xsl:value-of select="@change_by_name"/> (<xsl:value-of select="@change_by_badgeid" />)
            </td>
            <td>
                <xsl:value-of select="@change_ts_format" />
            </td>
            <td>
                <xsl:value-of select="@description" />
            </td>
        </tr>
    </xsl:template>

</xsl:stylesheet>
