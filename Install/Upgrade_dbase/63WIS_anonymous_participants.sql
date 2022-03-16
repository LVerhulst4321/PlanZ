## This script adds a field to Participants for people who do not want their name published online.
##
## Created by BC Holmes
##

ALTER TABLE Participants ADD COLUMN `anonymous` char(1) NOT NULL DEFAULT 'N';

INSERT INTO PatchLog (patchname) VALUES ('63WIS_anonymous_participants.sql');
