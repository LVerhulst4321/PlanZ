<?xml version="1.0" encoding="UTF-8" ?>
<!--
	PartSearchSessionsSubmit.xsl
	Created by Peter Olszowka on 2011-10-15 as SearchMySessions1.xsl
	Copyright (c) 2011-2020 Peter Olszowka. All rights reserved. See copyright document for more details.
-->
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
    <xsl:output encoding="UTF-8" indent="yes" method="xml"/>
    <xsl:param name="may_I" />
    <xsl:param name="conName" />
    <xsl:param name="trackIsPrimary" />
    <xsl:param name="showTrack" />
    <xsl:param name="showTags" />
    <xsl:param name="collapse_list" />
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
    <xsl:variable name="mayISubmitPanelInterests"
                select="$interested and $permissions_complete and $may_I" />

    <xsl:template match="/">
        <div class="row-fluid">
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
                        You will not be able to save your panel choices until you so do.
                    </span>
                </div>
            </div>
        </xsl:if>
        <xsl:choose>
            <xsl:when test="doc/query[@queryName='sessions']/row">
                <xsl:if test="$mayISubmitPanelInterests">
                    <div class="row">
                        <div class="col-auto offset-sm-2 alert alert-info">
                            If you have selected any sessions, please remember to <strong>SAVE</strong>
                            before leaving the page. (Use either "Save" buttons at the top or bottom.)
                        </div>
                    </div>
                </xsl:if>
                <form id="sessionInterestFRM" name="resform" method="POST" action="SubmitMySessions1.php">
                    <div class="row">
                        <div class="col-sm-10 offset-sm-1">
                            You will find the results of your search below. You can expand each item for more information.
                            <xsl:if test="$mayISubmitPanelInterests">
                                Check the box labelled "my session" to add a session to your interest list.
                            </xsl:if>
                        </div>
                        <div class="col-sm-1">
                            <button class="btn btn-primary" type="submit" name="save">
                                <xsl:if test="not($mayISubmitPanelInterests)"><xsl:attribute name="disabled">disabled</xsl:attribute></xsl:if>
                                <xsl:text>Save</xsl:text>
                            </button>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm-2 offset-sm-10 float-right">
                            <a class="btn btn-info" data-toggle="collapse" href=".multi-collapse" role="button" aria-expanded="false" aria-controls="{$collapse_list}">Expand All</a>
                        </div>
                    </div>
                    <hr />
                    <div class="row">
                        <xsl:choose>
                            <xsl:when test="$trackIsPrimary and $showTags">
                                <div class="col-0p75 pr-0">Session ID</div>
                                <div class="col-1p25">My Sessions</div>
                                <div class="col-1p75">Track</div>
                                <div class="col-3p25">Title</div>
                                <div class="col-1p5">Type</div>
                                <div class="col-2p75">Tags</div>
                                <div class="col-0p75">Expand</div>
                            </xsl:when>
                            <xsl:when test="$trackIsPrimary">
                                <div class="col-1p25">Session ID</div>
                                <div class="col-1p25">My Sessions</div>
                                <div class="col-3">Track</div>
                                <div class="col-4">Title</div>
                                <div class="col-1p75">Type</div>
                                <div class="col-0p75">Expand</div>
                            </xsl:when>
                            <xsl:when test="$showTrack">
                                <div class="col-0p75 pr-0">Session ID</div>
                                <div class="col-1p25">My Sessions</div>
                                <div class="col-2p75">Tags</div>
                                <div class="col-3p25">Title</div>
                                <div class="col-1p5">Type</div>
                                <div class="col-1p75">Track</div>
                                <div class="col-0p75">Expand</div>
                            </xsl:when>
                            <xsl:otherwise>
                                <div class="col-1p25">Session ID</div>
                                <div class="col-1p25">My Sessions</div>
                                <div class="col-3">Tags</div>
                                <div class="col-4">Title</div>
                                <div class="col-1p75">Type</div>
                                <div class="col-0p75">Expand</div>
                            </xsl:otherwise>
                        </xsl:choose>
                    </div>
                    <xsl:apply-templates select="doc/query[@queryName='sessions']/row" />
                    <div class="row">
                        <div class="col-sm-1 offset-sm-11">
                            <button class="btn btn-primary mt-4" type="submit" name="save">
                                <xsl:if test="not($mayISubmitPanelInterests)"><xsl:attribute name="disabled">disabled</xsl:attribute></xsl:if>
                                <xsl:text>Save</xsl:text>
                            </button>
                        </div>
                    </div>
                </form>
            </xsl:when>
            <xsl:otherwise>
                <div class="row">
                    <div class="col-3"> </div>
                    <div class="col-6 mt-4 alert alert-warning">
                        No sessions available for participant sign up matched your search.
                    </div>
                    <div class="col-3"> </div>
                </div>
            </xsl:otherwise>
        </xsl:choose>
    </div>
</xsl:template>

    <xsl:template match="/doc/query[@queryName='sessions']/row">
        <div class="card my-2 px-1 schedule-card">
            <div class="row">
                <xsl:choose>
                    <xsl:when test="$showTrack and $showTags">
                        <div class="col-0p75 pl-4 pr-0"><xsl:value-of select="@sessionid" /></div>
                        <div class="col-1p25 pl-1 pr-0">
                            <label class="mb-0">
                                <input type="checkbox" id="int{@sessoinid}" name="int{@sessionid}" class="interestsCHK"
                                    value="{@sessionid}">
                                    <xsl:if test="@badgeid">
                                        <xsl:attribute name="checked">checked</xsl:attribute>
                                    </xsl:if>
                                    <xsl:if test="not($mayISubmitPanelInterests)">
                                        <xsl:attribute name="disabled">disabled</xsl:attribute>
                                    </xsl:if>
                                </input>
                                My Session
                            </label>
                        </div>
                        <xsl:choose>
                            <xsl:when test="$trackIsPrimary">
                                <div class="col-1p75"><xsl:value-of select="@trackname" /></div>
                            </xsl:when>
                            <xsl:otherwise><div class="col-2p75"><xsl:value-of select="@taglist" /></div></xsl:otherwise>
                        </xsl:choose>
                        <div class="col-3p25"><xsl:value-of select="@title" /></div>
                        <div class="col-1p5"><xsl:value-of select="@typename" /></div>
                        <xsl:choose>
                            <xsl:when test="$trackIsPrimary">
                                <div class="col-2p75"><xsl:value-of select="@taglist" /></div>
                            </xsl:when>
                            <xsl:otherwise><div class="col-1p75"><xsl:value-of select="@trackname" /></div></xsl:otherwise>
                        </xsl:choose>
                        <div class="col-0p75 expander-wrapper">
                            <a href="#collapse-{@sessionid}" data-toggle="collapse" class="collapsed" aria-expanded="true"
                               aria-controls="#collapse-{@sessionid}">
                                <div class="expander">&#x2304;</div>
                            </a>
                        </div>
                    </xsl:when>
                    <xsl:otherwise>
                        <div class="col-1p25 pl-4"><xsl:value-of select="@sessionid" /></div>
                        <div class="col-1p25 pl-1 pr-0">
                            <label class="mb-0">
                                <input type="checkbox" id="int{@sessoinid}" name="int{@sessionid}" class="interestsCHK"
                                    value="{@sessionid}">
                                    <xsl:if test="@badgeid">
                                        <xsl:attribute name="checked">checked</xsl:attribute>
                                    </xsl:if>
                                    <xsl:if test="not($mayISubmitPanelInterests)">
                                        <xsl:attribute name="disabled">disabled</xsl:attribute>
                                    </xsl:if>
                                </input>
                                My Session
                            </label>
                        </div>
                        <xsl:choose>
                            <xsl:when test="$showTrack">
                                <div class="col-3"><xsl:value-of select="@trackname" /></div>
                            </xsl:when>
                            <xsl:otherwise><div class="col-3"><xsl:value-of select="@taglist" /></div></xsl:otherwise>
                        </xsl:choose>
                        <div class="col-4"><xsl:value-of select="@title" /></div>
                        <div class="col-1p75"><xsl:value-of select="@typename" /></div>
                        <div class="col-0p75 expander-wrapper">
                            <a href="#collapse-{@sessionid}" data-toggle="collapse" class="collapsed" aria-expanded="true"
                               aria-controls="#collapse-{@sessionid}">
                                <div class="expander">&#x2304;</div>
                            </a>
                        </div>
                    </xsl:otherwise>
                </xsl:choose>
            </div>
            <div id="collapse-{@sessionid}" class="collapse multi-collapse list-group list-group-flush">
                <div class="list-group-item py-1">
                    <div class="row">
                        <div class="col-0p75 pl-1 pr-0">Duration:</div>
                        <div class="col-1"><xsl:value-of select="@duration" /></div>
                        <div class="col-1 pl-0 pr-1">Description:</div>
                        <div class="col-5"><xsl:value-of select="@progguiddesc" /></div>
                        <div class="col-1p25">Prospective Participant Info:</div>
                        <div class="col-3"><xsl:value-of select="@persppartinfo" /></div>
                    </div>
                </div>
            </div>
        </div>
    </xsl:template>

</xsl:stylesheet>
