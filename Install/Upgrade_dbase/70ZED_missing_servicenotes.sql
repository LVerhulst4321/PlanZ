## This script adds a missing field to the PreviousSessions table.
##
##  Created by Leane Verhulst on August 24, 2021
##

## Add missing field
ALTER TABLE `PreviousSessions` ADD COLUMN `servicenotes` text DEFAULT NULL AFTER `notesforpart`;



INSERT INTO PatchLog (patchname) VALUES ('70ZED_missing_servicenotes.sql');
