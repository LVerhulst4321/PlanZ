<?xml version="1.0" encoding="UTF-8" ?>
<!--
	PartSearchSessions.xsl
	Created by Peter Olszowka on 2020-08-22.
	Copyright (c) 2020 Peter Olszowka. All rights reserved. See copyright document for more details.
-->
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
    <xsl:output encoding="UTF-8" indent="yes" method="xml"/>
    <xsl:param name="conName" />
    <xsl:param name="showTags" />
    <xsl:param name="showTrack" />
    <xsl:param name="ENABLE_SHARE_EMAIL_QUESTION" />
    <xsl:param name="ENABLE_USE_PHOTO_QUESTION" />
    <xsl:param name="ENABLE_ALLOW_STREAMING_QUESTION" />
    <xsl:param name="ENABLE_ALLOW_RECORDING_QUESTION" />
    <xsl:variable name="interested" select="/doc/query[@queryName='participant']/row/@interested = '1'"/>
    <xsl:variable name="share_email" select="/doc/query[@queryName='participant']/row/@share_email"/>
    <xsl:variable name="use_photo" select="/doc/query[@queryName='participant']/row/@use_photo"/>
    <xsl:variable name="allow_streaming" select="/doc/query[@queryName='participant']/row/@allow_streaming"/>
    <xsl:variable name="allow_recording" select="/doc/query[@queryName='participant']/row/@allow_recording"/>
    <xsl:variable name="permissions_complete"
                select="($share_email = 1 or $share_email = 2 or $ENABLE_SHARE_EMAIL_QUESTION) and
                        ($use_photo = 1 or $use_photo = 2 or $ENABLE_USE_PHOTO_QUESTION) and
                        ($allow_streaming = 1 or $allow_streaming = 2 or $ENABLE_ALLOW_STREAMING_QUESTION) and
                        ($allow_recording = 1 or $allow_recording = 2 or $ENABLE_ALLOW_RECORDING_QUESTION)"/>

    <xsl:template match="/">
        <div class="container-fluid">
            <xsl:if test="not($interested)">
                <div class="row">
                    <div class="col-auto offset-sm-2 alert alert-danger">
                        <h4>Warning!</h4>
                        <span>
                            You have not indicated in your profile that you will be attending <xsl:value-of select="$conName"/>.
                            You will not be able to save your panel choices until you so do.
                        </span>
                    </div>
                </div>
            </xsl:if>
            <xsl:if test="not($permissions_complete)">
                <div class="row">
                    <div class="col-auto offset-sm-2 alert alert-danger">
                        <h4>Warning!</h4>
                        <span>
                            You have not answered all of the permissions questions in your profile.
                            You will not be able to save your panel choices
                            until you so do.
                        </span>
                    </div>
                </div>
            </xsl:if>
            <form class="container mt-2 mb-4" method="POST" action="PartSearchSessionsSubmit.php">
                <div class="row mb-3">
                    <xsl:choose>
                        <xsl:when test="$showTrack">
                            <div class="col-auto">
                                <label for="track-sel">Track:</label>
                                <select id="track-sel" class="tcell" name="track">
                                    <option value="0">ANY</option>
                                    <xsl:apply-templates select="doc/query[@queryName='tracks']/row" />
                                </select>
                            </div>
                        </xsl:when>
                        <xsl:otherwise>
                            <input type="hidden" name="track" value="0" />
                        </xsl:otherwise>
                    </xsl:choose>
                    <div class="col-auto">
                        <label for="title-txtinp">Title Search:</label>
                        <input id="title-txtinp" name="title" size="35" placeholder="Session title" />
                    </div>
                    <xsl:if test="not($showTags)">
                        <div class="col-auto">
                            <button class="btn btn-primary" type="submit" value="search">Search</button>
                        </div>
                    </xsl:if>
                </div>
                <xsl:choose>
                    <xsl:when test="$showTags">
                        <div class="row mb-3">
                            <div class="col-auto">
                                <div class="tag-chk-legend">Tags:</div>
                            </div>
                            <div class="col-auto">
                                <div class="tag-chk-container">
                                    <xsl:apply-templates select="doc/query[@queryName='tags']/row" />
                                </div>
                            </div>
                            <div class="col-auto align-self-center">
                                <label class="tag-match-label"><input type="radio" id="tagmatch1" name="tagmatch" class="tag-match-radio" value="any" checked="checked" />Match Any Selected</label>
                                <label class="tag-match-label"><input type="radio" id="tagmatch2" name="tagmatch" class="tag-match-radio" value="all" />Match All Selected</label>
                            </div>
                            <div class="col-auto align-self-end">
                                <button class="btn btn-primary" type="submit" value="search">Search</button>
                            </div>
                        </div>
                    </xsl:when>
                    <xsl:otherwise>
                        <input type="hidden" name="tags[]" value="0" />
                    </xsl:otherwise>
                </xsl:choose>

                <p>On the following page, you can select sessions for participation. You must <strong>SAVE</strong> your changes
                    before leaving the page or your selections will not be recorded.</p>
                <p><strong>Clicking Search without making any selections will display all sessions.</strong></p>
            </form>

        </div>
    </xsl:template>

    <xsl:template match="/doc/query[@queryName='tracks']/row">
        <option value="{@trackid}"><xsl:value-of select="@trackname" /></option>
    </xsl:template>

    <xsl:template match="/doc/query[@queryName='tags']/row">
        <div class="tag-chk-label-wrapper">
            <label class="tag-chk-label">
                <input type="checkbox" name="tags[]" class="tag-chk" value="{@tagid}" />
                <xsl:value-of select="@tagname" />
            </label>
        </div>

    </xsl:template>

</xsl:stylesheet>
