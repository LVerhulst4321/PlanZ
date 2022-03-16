<?xml version='1.0' encoding="UTF-8"?>
<!-- Created by Peter Olszowka on 2020-04-21;
     Copyright (c) 2020 Peter Olszowka. All rights reserved. See copyright document for more details. -->
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
    <xsl:param name="control" select="''" />
    <xsl:param name="controliv" select="''" />
    <xsl:param name="user_name" select="''" />
    <xsl:param name="badgeid" select="''" />
    <xsl:param name="error_message" select="''" />
    <xsl:template match="/">
        <div class="container">
            <xsl:if test="$error_message">
                <p class="alert alert-danger mt-3"><xsl:value-of select="$error_message" /></p>
            </xsl:if>
            <form method="POST" action="ForgotPasswordResetSubmit.php" class="well form-horizontal">
                <div class="card mt-3 mb-3">
                    <div class="card-body">
                        <input type="hidden" id="control" name="control" value="{$control}" />
                        <input type="hidden" id="controliv" name="controliv" value="{$controliv}" />
                        <p>Enter new password for <xsl:value-of select="$user_name" />, badgeid: <xsl:value-of select="$badgeid" /></p>
                        <div class="form-group">
                            <label for="password" class="control-label">Password</label>
                            <div class="row">
                                <div class="col-10">
                                    <input class="form-control" type="password" name="password" id="password" />
                                </div>
                                <div class="col-2">
                                    <button class="btn px-0" type="button" id="revealPassword"><img style="width: 1.5rem;" src="images/eye.svg" /></button>
                                </div>
                            </div>
                            <small id="passwords-too-short" class="form-text text-danger hidden">Passwords must be 6 characters or longer.</small>
                        </div>
                        <div class="form-group">
                            <label for="cpassword" class="control-label">Confirm password</label>
                            <div class="row">
                                <div class="col-10">
                                    <input class="form-control" type="password" name="cpassword" id="cpassword" />
                                </div>
                            </div>
                            <small id="passwords-dont-match" class="form-text text-danger hidden">Passwords do not match.</small>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" id="submit-button" class="btn btn-primary">Submit</button>
                    </div>
                </div>
                <p>
                    After changing your password, you will be taken to the login page.
                </p>
            </form>
        </div>
    </xsl:template>
</xsl:stylesheet>