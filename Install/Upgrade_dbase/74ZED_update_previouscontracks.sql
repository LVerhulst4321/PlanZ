## This script adds a missing field to the PreviousConTracks table.
##
##  Created by Leane Verhulst on August 27, 2021
##

## Add missing field
ALTER TABLE `PreviousConTracks` ADD COLUMN `display_order` int(11) NOT NULL AFTER `trackname`;
ALTER TABLE `PreviousConTracks` ADD COLUMN `selfselect` tinyint(1) NOT NULL AFTER `display_order`;



INSERT INTO PatchLog (patchname) VALUES ('74ZED_update_previouscontracks.sql');
