<?xml version='1.0' encoding="UTF-8"?>
<!--
    Created by Peter Olszowka on 2020-07-20;
    Copyright (c) 2020-2021 Peter Olszowka. All rights reserved. See copyright document for more details.
-->
<xsl:stylesheet version="1.1" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
    <xsl:param name="title" select="''"/>
    <!-- Page title -->
    <xsl:param name="reportMenuList" select="''"/>
    <xsl:param name="badgename" select="'Current User'"/>
    <xsl:param name="PARTICIPANT_PHOTOS" select="'0'"/>
    <xsl:param name="AUTO_SCHEDULER" select="'0'"/>
    <xsl:param name="emailAvailable" select="'0'"/>
    <xsl:param name="isToolbarPresent" select="'0'"/>
    <!-- Set of <a> elements; contents of ReportMenuBS4Include.php -->
    <xsl:variable name="ConfigureReports"
        select="/doc/query[@queryname='permission_set']/row[@permatomtag='ConfigureReports']"/>
    <xsl:variable name="AdminPhases" select="/doc/query[@queryname='permission_set']/row[@permatomtag='AdminPhases']"/>
    <xsl:variable name="Administrator"
        select="/doc/query[@queryname='permission_set']/row[@permatomtag='Administrator']"/>
    <xsl:variable name="ExportSchedule" select="/doc/query[@queryname='permission_set']/row[@permatomtag='ExportSchedule']"/>
    <xsl:variable name="EditAnyTable" select="/doc/query[@queryname='permission_set']/row[@permatomtag='ce_All' or
        @permatomtag='ce_AgeRanges' or @permatomtag='ce_BioEditStatuses' or @permatomtag='ce_Credentials' or @permatomtag='ce_Divisions' or
        @permatomtag='ce_EmailCC' or @permatomtag='ce_EmailFrom' or @permatomtag='ce_EmailTo' or @permatomtag='ce_Features' or
        @permatomtag='ce_Interests' or @permatomtag='ce_KidsCategories' or @permatomtag='ce_LanguageStatuses' or 
        @permatomtag='ce_Locations' or @permatomtag='ce_PhotoDenialReasons' or @permatomtag='ce_Pronouns' or @permatomtag='ce_PubStatuses' or
        @permatomtag='ce_RegTypes' or @permatomtag='ce_Roles' or @permatomtag='ce_RoomColors' or @permatomtag='ce_Rooms' or @permatomtag='ce_RoomSets' or
        @permatomtag='ce_RoomHasSet' or @permatomtag='ce_Services' or @permatomtag='ce_ServiceTypes' or @permatomtag='ce_SessionStatuses' or
        @permatomtag='ce_Tags' or @permatomtag='ce_Times' or @permatomtag='ce_Tracks' or @permatomtag='ce_Types']"/>
    <xsl:template match="/">
        <nav id="staffNav">
            <xsl:choose>
                <xsl:when test="$isToolbarPresent = 1">
                    <xsl:attribute name="class">
                        navbar navbar-expand-lg navbar-dark bg-dark
                    </xsl:attribute>
                </xsl:when>
                <xsl:otherwise>
                    <xsl:attribute name="class">
                        navbar navbar-expand-lg navbar-dark bg-dark mb-3
                    </xsl:attribute>
                </xsl:otherwise>
            </xsl:choose>
            <a class="navbar-brand py-1" href="#">
                <xsl:value-of select="$title"/>
            </a>
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent"
                aria-controls="navbarSupportedContent"
                aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"/>
            </button>
            <div class="collapse navbar-collapse flex-wrap" id="navbarSupportedContent">
                <ul class="navbar-nav mr-4">
                    <li class="nav-item dropdown mr-2 py-0">
                        <a class="nav-link dropdown-toggle py-1" href="#" id="navbarSessionsDropdown" role="button"
                           data-toggle="dropdown"
                           aria-haspopup="true" aria-expanded="false">
                            Sessions
                        </a>
                        <div class="dropdown-menu" aria-labelledby="navbarSessionsDropdown">
                            <a class="dropdown-item" href="StaffSearchSessions.php">Search Sessions</a>
                            <a class="dropdown-item" href="CreateSession.php">Create New Session</a>
                            <a class="dropdown-item" href="ViewSessionCountReport.php">View Session Counts</a>
                            <a class="dropdown-item" href="ViewAllSessions.php">View All Sessions</a>
                            <a class="dropdown-item" href="ViewPrecis.php?showlinks=0">View Precis</a>
                            <a class="dropdown-item" href="ViewPrecis.php?showlinks=1">View Precis with Links</a>
                            <a class="dropdown-item" href="StaffSearchPreviousSessions.php">Import Sessions</a>
                            <a class="dropdown-item" href="SessionHistory.php">Session History</a>
                        </div>
                    </li>
                    <li class="nav-item dropdown mr-2 py-0">
                        <a class="nav-link dropdown-toggle py-1" href="#" id="navbarParticipantsDropdown" role="button"
                           data-toggle="dropdown"
                           aria-haspopup="true" aria-expanded="false">
                            Participants
                        </a>
                        <div class="dropdown-menu" aria-labelledby="navbarParticipantsDropdown">
                            <a class="dropdown-item" href="AdminParticipants.php">Administer</a>
                            <xsl:if test="$PARTICIPANT_PHOTOS = '1'">
                              <a class="dropdown-item" href="AdminPhotos.php">Photos</a>
                            </xsl:if>
                            <a class="dropdown-item" href="InviteParticipants.php">Invite to a Session</a>
                            <a class="dropdown-item" href="StaffAssignParticipants.php">Assign to a Session</a>
                            <xsl:if test="$emailAvailable = '1' and /doc/query[@queryname='permission_set']/row[@permatomtag='SendEmail']">
                                <a class="dropdown-item" href="StaffSendEmailCompose.php">Send email</a>
                            </xsl:if>
                            <xsl:if test="/doc/query[@queryname='permission_set']/row[@permatomtag='CreateUser']">
                                <a class="dropdown-item" href="AddUser.php">Create User</a>
                            </xsl:if>
                        </div>
                    </li>
                    <li class="nav-item dropdown mr-2 py-0">
                        <a class="nav-link dropdown-toggle py-1" href="#" id="navbarReportsDropdown" role="button"
                           data-toggle="dropdown"
                           aria-haspopup="true" aria-expanded="false">
                            Reports
                        </a>
                        <div class="dropdown-menu" aria-labelledby="navbarReportsDropdown">
                            <xsl:choose>
                                <xsl:when test="$reportMenuList != ''">
                                    <xsl:value-of select="$reportMenuList" disable-output-escaping="yes"/>
                                </xsl:when>
                                <xsl:otherwise>
                                    <span class="dropdown-item-text alert alert-danger">Report menus not built!</span>
                                </xsl:otherwise>
                            </xsl:choose>
                            <div class="dropdown-divider"/>
                            <a class="dropdown-item" href='staffReportsInCategory.php'>All Reports</a>
                        </div>
                    </li>
                    <li class="nav-item dropdown mr-2 py-0">
                        <a class="nav-link dropdown-toggle py-1" href="#" id="navbarSchedulingDropdown" role="button"
                           data-toggle="dropdown"
                           aria-haspopup="true" aria-expanded="false">
                            Scheduling
                        </a>
                        <div class="dropdown-menu" aria-labelledby="navbarSchedulingDropdown">
                            <xsl:if test="$AUTO_SCHEDULER = '1'">
                                <a class="dropdown-item" href="Autoscheduler.php">Auto-Scheduler</a>
                            </xsl:if>
                            <a class="dropdown-item" href="MaintainRoomSched.php">Maintain Room Schedule</a>
                            <a class="dropdown-item" href="StaffMaintainSchedule.php">Grid Scheduler</a>
                            <a class="dropdown-item" href="CurrentSchedule.php">Current Schedule</a>
                        </div>
                    </li>
                    <li class="nav-item py-0 mr-2">
                        <a class="nav-link py-1" href="StaffPage.php">Overview</a>
                    </li>
                    <li class="nav-item py-0 mr-4">
                        <a class="nav-link py-1" href="Tools.php">Tools</a>
                    </li>
                </ul>
                <form method="post" action="ShowSessions.php" class="form-inline my-0 my-lg-0 mr-4">
                    <input type="text" id="searchtitle" name="searchtitle" size="28"
                           class="form-control mr-sm-2 h-100 bg-secondary text-white"
                           placeholder="Search for sessions by title" aria-label="Search"/>
                    <input type="hidden" value="ANY" name="track"/>
                    <input type="hidden" value="ANY" name="status"/>
                    <input type="hidden" value="ANY" name="type"/>
                    <input type="hidden" value="" name="sessionid"/>
                    <input type="hidden" value="ANY" name="divisionid"/>
                </form>
                <xsl:variable name="AdminMenu" select="$AdminPhases or $ConfigureReports or $Administrator or $EditAnyTable or $ExportSchedule" />
                <xsl:if test="$AdminMenu">
                    <div class="navbar-nav">
                        <div class="nav-item dropdown mr-4 py-0">
                            <a class="nav-link dropdown-toggle py-1" href="#" id="navbarAdminDropdown" role="button"
                               data-toggle="dropdown"
                               aria-haspopup="true" aria-expanded="false">
                                Admin
                            </a>
                            <div class="dropdown-menu" aria-labelledby="navbarAdminDropdown">
                                <xsl:if test="$AdminPhases">
                                    <a class="dropdown-item" href="AdminPhases.php">Administer Phases</a>
                                </xsl:if>
                                <xsl:if test="$ConfigureReports">
                                    <a class="dropdown-item" href="BuildReportMenus.php">Build Report Menus</a>
                                </xsl:if>
                                <xsl:if test="$Administrator">
                                    <a class="dropdown-item" href="EditCustomText.php">Edit Custom Text</a>
                                    <a class="dropdown-item" href="EditSurvey.php">Edit Survey</a>
                                </xsl:if>
                                <xsl:if test="$EditAnyTable">
                                    <a class="dropdown-item" href="ConfigTableEditor.php">Edit Configuration Tables</a>
                                </xsl:if>
                                <xsl:if test="$ExportSchedule">
                                    <a class="dropdown-item" href="StaffCreateKonOpas.php">Update KonOpas and ConClar</a>
                                </xsl:if>
                            </div>
                        </div>
                    </div>
                </xsl:if>
                <div class="navbar-nav ml-auto mr-2">
                    <div class="nav-item py-0">
                        <a id="ParticipantView" class="nav-link py-1" href="welcome.php">Participant View</a>
                    </div>
                </div>
                 <div class="navbar-nav dropdown py-0">
                    <a class="nav-link dropdown-toggle py-1" href="#" role="button"
                        data-toggle="dropdown"
                        aria-haspopup="true" aria-expanded="false">
                        <xsl:value-of select="$badgename"/>
                    </a>
                    <div class="dropdown-menu dropdown-menu-right" aria-labelledby="navbarSessionsDropdown">
                        <a class="dropdown-item" href="./my_contact.php">Profile</a>
                        <a class="dropdown-item" href="MySchedule.php">My Schedule</a>
                        <a class="dropdown-item" href="my_sched_constr.php">Availability</a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item" href="./logout.php">Log out</a>
                    </div>
                </div>
            </div>
        </nav>
    </xsl:template>
</xsl:stylesheet>