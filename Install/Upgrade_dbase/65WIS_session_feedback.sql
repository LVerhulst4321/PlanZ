## This script adds a couple of fields for the new session feedback functionality.
##
## Created by BC Holmes
##

ALTER TABLE `ParticipantSessionInterest` ADD COLUMN attend int;
ALTER TABLE `ParticipantSessionInterest` ADD COLUMN attend_type int;

INSERT INTO PatchLog (patchname) VALUES ('65WIS_session_feedback.sql');