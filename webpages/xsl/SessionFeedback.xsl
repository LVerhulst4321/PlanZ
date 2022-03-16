<?xml version="1.0" encoding="UTF-8" ?>
<!--
	Created by BC Holmes on 2021-12-16;
-->
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
	<xsl:output encoding="UTF-8" indent="yes" method="html" />
  <xsl:template match="/">
    <div class="container">
        <xsl:for-each select="/doc/query[@queryName='participant_info']/row">
          <xsl:if test="@interested = '0'">
            <div class="alert alert-warning">According to your profile, you haven't indicated whether or not you want to be assigned to sessions (panels, etc).
              If that's inaccurate, you may want to <a class="alert-link" href="./my_contact.php">update your profile</a> before continuing.</div>
          </xsl:if>
          <xsl:if test="@interested = '2'">
            <div class="alert alert-warning">According to your profile, you aren't interested in being assigned to sessions (panels, etc). 
              If that's inaccurate, you may want to <a class="alert-link" href="./my_contact.php">update your profile</a> before continuing.</div>
          </xsl:if>
        </xsl:for-each>

        <div class="card">
            <div class="card-header">
                <h2>Session Interest Survey</h2>
            </div>
            <div class="card-body">
                  <div class="row">
                    <div class="form-group col-md-6 mb-2">
                      <label for="filter" class="sr-only">Filter</label>
                      <div class="input-group">
                        <input id="filter" type="text" class="form-control" placeholder="Filter..." />
                        <span class="input-group-append">
                          <button id="clearFilter" class="btn btn-secondary" type="button">
                            <i class="bi bi-x"> </i>
                          </button>
                        </span>
                      </div>
                    </div>
                  </div>

                <div id="load-spinner" class="text-center" style="display: none">
                  <div class="spinner-border m-5" role="status">
                    <span class="sr-only">Loading...</span>
                  </div>
                </div>

                <p>Please specify your interest in the following sessions.</p>

                <div id="session-list">
                </div>
            </div>
        </div>
    </div>
    <script type="text/javascript" src="javascript/zambiaExtension.js" />
    <script type="text/javascript" src="javascript/zambiaExtensionFeedback.js" />
  </xsl:template>
</xsl:stylesheet>
