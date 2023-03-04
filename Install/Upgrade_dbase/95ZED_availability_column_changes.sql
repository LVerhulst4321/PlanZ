##
## The UI screens for the My Availability seem to allow for a lot of text, but the
## back-end database only seems to allow for 255 characters. Alter some of the column definitions
## to allow for more text.
##
## Created by BC Holmes
##

ALTER TABLE ParticipantAvailability CHANGE preventconflict preventconflict TEXT DEFAULT NULL, CHANGE otherconstraints otherconstraints TEXT DEFAULT NULL;

INSERT INTO PatchLog (patchname) VALUES ('95ZED_availability_column_changes.sql');