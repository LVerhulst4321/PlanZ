<?xml version="1.0" encoding="UTF-8" ?>
<xsl:stylesheet version="1.1" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
    <xsl:param name="additionalCss" select="''" />
    <xsl:param name="paper" select="'letter'" />
    <xsl:output encoding="UTF-8" indent="yes" method="html" />
    <xsl:template match="/">
        <html>
            <head>
                <link rel="stylesheet" href="css/zambia_print.css" type="text/css" />
                <xsl:if test="not($additionalCss = '')">
                    <link rel="stylesheet" type="text/css">
                        <xsl:attribute name="href">
                            <xsl:value-of select="$additionalCss" />
                        </xsl:attribute>
                    </link>
                </xsl:if>
                <title>Room Schedule</title>
            </head>
            <body class="room-sign-body">
            <xsl:apply-templates select="doc/room"/>
            </body>
        </html>
    </xsl:template>

    <xsl:template match="doc/room">
        <section>
            <xsl:attribute name="class">
                <xsl:value-of select="'room-sign'" />
                <xsl:value-of select="' paper-'" />
                <xsl:value-of select="$paper" />
            </xsl:attribute>
            <xsl:for-each select="day">
                <div class="page portrait">
                    <div>
                        <h1 class="title"><xsl:value-of select="../@name" /></h1>
                        <p class="day"><xsl:value-of select="@name" /></p>
                    </div>
                    <xsl:for-each select="session">
                        <div class="session-row">
                            <div class="session-time"><xsl:value-of select="@startTime" />&#8211;<xsl:value-of select="@endTime" /></div>
                            <div class="session-title"><xsl:value-of select="@title" /></div>
                        </div>
                    </xsl:for-each>
                </div>
            </xsl:for-each>
        </section>
    </xsl:template>
</xsl:stylesheet>