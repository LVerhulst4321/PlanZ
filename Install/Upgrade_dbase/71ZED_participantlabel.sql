## This script adds a new field to the sessions table.
##
##  Created by Leane Verhulst on August 24, 2021
##

## Add new field
ALTER TABLE `Sessions` ADD COLUMN `participantlabel` varchar(50) NOT NULL DEFAULT 'Panelists' AFTER `notesforprog`;
ALTER TABLE `PreviousSessions` ADD COLUMN `participantlabel` varchar(50) DEFAULT NULL AFTER `notesforprog`;



INSERT INTO PatchLog (patchname) VALUES ('71ZED_participantlabel.sql');
