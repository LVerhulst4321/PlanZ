<?xml version='1.0' encoding="UTF-8"?>
<!--
    Created by Peter Olszowka on 2020-07-27;
    Copyright (c) 2020-2021 Peter Olszowka. All rights reserved. See copyright document for more details.
-->
<xsl:stylesheet version="1.1" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
  <xsl:param name="title" select="''" />
  <xsl:param name="PARTICIPANT_PHOTOS" select="'0'"/>
  <xsl:param name="survey" select="'false'" />
  <xsl:param name="my_suggestions" select="'true'" />
  <xsl:param name="SessionFeedback" select="'true'" />
  <xsl:param name="SessionInterests" select="'true'" />
  <xsl:template match="/">
    <nav id="participantNav" class="navbar navbar-expand-lg navbar-dark bg-dark mb-3">
      <span class="navbar-brand py-1">
        <xsl:value-of select="$title"/>
      </span>
      <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent"
        aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon" />
      </button>
      <div class="collapse navbar-collapse flex-wrap" id="navbarSupportedContent">
        <ul class="navbar-nav">
          <li class="nav-item py-0">
            <a class="nav-link py-1" href="welcome.php">Overview</a>
          </li>
          <li class="nav-item py-0">
            <a class="nav-link py-1" href="my_contact.php">Profile</a>
          </li>
          <li class="nav-item py-0">
            <a class="nav-link py-1" href="my_details.php">Personal Details</a>
          </li>
          <xsl:if test="$PARTICIPANT_PHOTOS = '1'">
            <li class="nav-item py-0">
              <a class="nav-link py-1" href="my_photo.php">Photo</a>
            </li>
          </xsl:if>
          <xsl:if test="$survey">
            <li class="nav-item py-0">
              <a class="nav-link py-1" href="PartSurvey.php">Survey</a>
            </li>
          </xsl:if>
          <xsl:if test="/doc/query[@queryname='permission_set']/row[@permatomtag='my_availability']">
            <li class="nav-item py-0">
              <a class="nav-link py-1" href="my_sched_constr.php">Availability</a>
            </li>
          </xsl:if>
          <li class="nav-item py-0">
            <a class="nav-link py-1" href="my_interests.php">General Interests</a>
          </li>
          <xsl:if test="/doc/query[@queryname='permission_set']/row[@permatomtag='search_panels']">
            <li class="nav-item py-0">
              <a class="nav-link py-1" href="PartSearchSessions.php">Search Sessions</a>
            </li>
          </xsl:if>
          <xsl:if test="$SessionInterests">
            <li class="nav-item py-0">
              <a class="nav-link py-1" href="PartPanelInterests.php">Session Interests</a>
            </li>
          </xsl:if>
          <xsl:if test="$SessionFeedback">
            <li class="nav-item py-0">
              <a class="nav-link py-1" href="SessionFeedback.php">Interest Survey</a>
            </li>
          </xsl:if>
          <xsl:if test="$my_suggestions">
            <li class="nav-item py-0">
              <a class="nav-link py-1" href="my_suggestions.php">My Suggestions</a>
            </li>
          </xsl:if>
          <xsl:if test="/doc/query[@queryname='permission_set']/row[@permatomtag='my_schedule']">
            <li class="nav-item py-0">
              <a class="nav-link py-1" href="MySchedule.php">My Schedule</a>
            </li>
          </xsl:if>
          <xsl:if test="/doc/query[@queryname='permission_set']/row[@permatomtag='BrainstormSubmit']">
            <li class="nav-item py-0 ml-4">
              <a class="nav-link py-1" href="./brainstorm/">Suggest a Session</a>
            </li>
          </xsl:if>
        </ul>
        <xsl:if test="/doc/query[@queryname='permission_set']/row[@permatomtag='Staff']">
          <div class="navbar-nav ml-auto">
            <div class="nav-item py-0">
              <a id="StaffView" class="nav-link py-1" href="StaffPage.php">Staff View</a>
            </div>
          </div>
        </xsl:if>
      </div>
    </nav>
  </xsl:template>
</xsl:stylesheet>