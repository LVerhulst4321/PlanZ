<?xml version="1.0" encoding="UTF-8" ?>
<!--
	Created by Peter Olszowka on 2020-06-01;
	Copyright (c) 2020 Peter Olszowka. All rights reserved. See copyright document for more details.
-->
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
  <xsl:param name="UpdateMessage" select="''"/>
  <xsl:param name="control" select="''"/>
  <xsl:param name="controliv" select="''"/>
  <xsl:output encoding="UTF-8" indent="yes" method="html" />
  <xsl:template match="/">
    <div class="container mt-3">
    <xsl:choose>
      <xsl:when test="$UpdateMessage != ''">
        <div class="alert alert-success">
          <xsl:value-of select="$UpdateMessage" disable-output-escaping="yes"/>
        </div>
      </xsl:when>
    </xsl:choose>
    <form name="phaseform" method="POST" action="AdminPhases.php">
      <input type="hidden" id="PostCheck" name="PostCheck" value="POST"/>
      <input type="hidden" id="control" name="control" value="{$control}" />
      <input type="hidden" id="controliv" name="controliv" value="{$controliv}" />
      <div class="card">
        <div class="card-header">
          <h4>Current Phase Status</h4>
        </div>
        <div class="card-body">
          <table id="phase_table" class="table table-sm table-striped">
            <thead>
              <tr>
                <th>ID</th>
                <th>Current Status</th>
                <th>Phase Name</th>
                <th>Notes</th>
              </tr>
            </thead>
            <tbody>
              <xsl:for-each select="/doc/query[@queryName='phase_info']/row">
                <tr>
                  <td class="text-center align-middle">
                    <xsl:attribute name="id">
                      <xsl:text>phase_id_num_</xsl:text>
                      <xsl:value-of select="@phaseid"/>
                    </xsl:attribute>
                    <xsl:value-of select="@phaseid"/>
                  </td>
                  <td class="align-middle">
                    <select class="form-control">
                      <xsl:attribute name="id">
                        <xsl:text>phase_id_</xsl:text>
                        <xsl:value-of select="@phaseid"/>
                      </xsl:attribute>
                      <xsl:attribute name="name">
                        <xsl:text>select_phase_</xsl:text>
                        <xsl:value-of select="@phaseid"/>
                      </xsl:attribute>
                      <xsl:attribute name="onchange">
                        <xsl:text>ChangePhase(</xsl:text>
                        <xsl:value-of select="@phaseid"/>
                        <xsl:text>, this);</xsl:text>
                      </xsl:attribute>
                      <option value="0">
                        <xsl:if test="@current = 0">
                          <xsl:attribute name="selected">selected</xsl:attribute>
                        </xsl:if>
                        Inactive
                      </option>
                      <option value="1">
                        <xsl:if test="@current = 1">
                          <xsl:attribute name="selected">selected</xsl:attribute>
                        </xsl:if>
                        Active
                      </option>
                    </select>
                  </td>
                  <td class="align-middle">
                    <xsl:attribute name="id">
                      <xsl:text>phase_name_</xsl:text>
                      <xsl:value-of select="@phaseid"/>
                    </xsl:attribute>
                    <xsl:value-of select="@phasename"/>
                  </td>
                  <td class="align-middle">
                    <xsl:value-of select="@notes"/>
                  </td>
                </tr>
              </xsl:for-each>
            </tbody>
          </table>
        </div>
        <div class="card-footer text-right">
          <button class="btn btn-primary" type="submit" value="save" onclick="mysubmit()">Save</button>
        </div>
      </div>
    </form>
    </div>
  </xsl:template>
</xsl:stylesheet>
