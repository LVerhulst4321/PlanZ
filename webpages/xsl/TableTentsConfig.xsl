<?xml version='1.0' encoding="UTF-8"?>
<xsl:stylesheet version="1.1" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
    <xsl:template match="/">

        <form action="TableTents.php" method="GET" target="_blank">
            <div class="card mt-3">
                <div class="card-header">
                    <h2>Table Tents</h2>
                </div>
                <div class="card-body">
                    <p>The Table Tents feature creates printable "tents" that name the participants on a session (typically used for panels).
                    In many cons, the tents are printed out in the Green Room, and the panel moderator picks them up just before the panel 
                    is scheduled to begin.</p>

                    <div class="row">
                        <label class="col-md-3">Type of table tent:</label>
                        <label class="radio col-md-2">
                            <input type="radio" id="fold-2" class="tent-type" name="tent-type" value="fold-2" checked="checked" />
                            Fold in 2
                        </label>
                        <label class="radio col-md-2">
                            <input type="radio" id="fold-3" class="tent-type" name="tent-type" value="fold-3" />
                            Fold in 3
                        </label>
                    </div>

                    <div class="fold-2">
                        <p>To use these, you'll need a prism-shaped stand. The stands are reusable, and can remain in a room. When a new group of
                        session participants comes in, they can fold the tents in half and lay them over the stand, with the name portion visible to
                        the audience.</p>

                        <div class="text-center">
                            <img src="./images/table-tent.svg" style="height: 325px; width: 500px;" />
                        </div>
                    </div>

                    <div class="fold-3 hidden">
                        <p>These tents are self supporting. Fold in 3 and display with guest name facing forwards.</p>

                        <div class="text-center">
                            <img src="./images/table-tent-fold3.svg" style="height: 325px; width: 500px;" />
                        </div>
                    </div>

                    <div class="row form-group">
                        <div class="col-md-3">
                            <label for="paper">Paper type:</label>
                        </div>
                        <div class="col-md-4">
                            <select id="paper" name="paper" class="form-control">
                                <option value="LETTER">Letter-sized</option>
                                <option value="A4">A4-sized</option>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <label class="col-md-3">Print fold lines:</label>
                        <label class="radio col-md-1">
                            <input type="radio" id="fold-yes" class="fold-lines" name="fold-lines" value="yes" checked="checked" />
                            Yes
                        </label>
                        <label class="radio col-md-1">
                            <input type="radio" id="fold-no" class="fold-lines" name="fold-lines" value="no" />
                            No
                        </label>
                    </div>

                    <div class="row">
                        <label class="col-md-3">Print separator pages:</label>
                        <label class="radio col-md-1">
                            <input type="radio" id="separator-yes" class="separator-pages" name="separator-pages" value="yes" checked="checked" />
                            Yes
                        </label>
                        <label class="radio col-md-1">
                            <input type="radio" id="separator-no" class="separator-pages" name="separator-pages" value="no" />
                            No
                        </label>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary">Generate</button>
                </div>
            </div>
        </form>

    </xsl:template>
</xsl:stylesheet>