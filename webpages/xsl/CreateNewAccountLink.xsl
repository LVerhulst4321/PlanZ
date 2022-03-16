<?xml version='1.0' encoding="UTF-8"?>
<!-- Created by BC Holmes on 2022-01-29 -->
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
    <xsl:param name="control" select="''" />
    <xsl:param name="controliv" select="''" />
    <xsl:template match="/">
        <div class="container mt-3">
            <form method="POST" action="ForgotPasswordResetSubmit.php">
                <div class="card mb-3">
                    <div class="card-header">
                        <h4>Create New Account</h4>
                    </div>
                    <div class="card-body">
                        <input type="hidden" id="control" name="control" value="{$control}" />
                        <input type="hidden" id="controliv" name="controliv" value="{$controliv}" />
                        <p>Enter the following information:</p>
                        <div class="form-group">
                            <label for="name" class="control-label">Name (as you'd like it to appear on your badge):</label>
                            <div class="row">
                                <div class="col-10">
                                    <input class="form-control" type="text" name="name" id="name" />
                                </div>
                            </div>
                            <small id="name-required" class="form-text text-danger" style="display: none">Name is required.</small>
                        </div>
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
                            <small id="passwords-too-short" class="form-text text-danger" style="display: none">Passwords must be 8 characters or longer.</small>
                        </div>
                        <div class="form-group">
                            <label for="cpassword" class="control-label">Confirm password</label>
                            <div class="row">
                                <div class="col-10">
                                    <input class="form-control" type="password" name="cpassword" id="cpassword" />
                                </div>
                            </div>
                            <small id="passwords-dont-match" class="form-text text-danger" style="display: none">Passwords do not match.</small>
                        </div>
                        <p>
                            After entering the above data, you will be taken to the login page.
                        </p>
                    </div>
                    <div class="card-footer">
                        <button type="submit" id="submit-button" class="btn btn-primary" disabled="true">Create</button>
                    </div>
                </div>
            </form>
        </div>
        <script type="text/javascript" src="js/zambiaExtension.js" />
        <script type="text/javascript" src="js/zambiaExtensionCreateAccount.js" />
    </xsl:template>
</xsl:stylesheet>