## This script adds a new column to the ParticipantOnSession to track
## whether or not a Participant has confirmed their attendance on the
## session.
##
## Currently, the use of the ParticipantOnSession and ParticipantOnSessionHistory
## table is complex and often driven by triggers. For now, I am ignoring 
## the triggers, and will update the table directly when the participant 
## confirms.
##
## Created by BC Holmes
##
ALTER TABLE `ParticipantOnSession` ADD COLUMN `confirmed` varchar(16);
ALTER TABLE `ParticipantOnSession` ADD COLUMN `notes` text;

INSERT INTO PatchLog (patchname) VALUES ('76WIS_add_confirmation_column.sql');