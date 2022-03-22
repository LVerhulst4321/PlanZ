## This script adds a new column to the Divisions table to identify
## which divisions will allow a participant to enter session feedback/interest.
##
## Created by Leane Verhulst
##

ALTER TABLE `Divisions` ADD COLUMN `allow_partSessionInterest` tinyint(1) NOT NULL DEFAULT 0;

INSERT INTO PatchLog (patchname) VALUES ('79ZED_add_partsessioninterest_field.sql');