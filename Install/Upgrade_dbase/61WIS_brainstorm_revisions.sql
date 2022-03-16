## This script adds new columns to Divisions and Tracks for the new brainstorm functionality.
##
## Created by BC Holmes
##

ALTER TABLE `Divisions` ADD COLUMN email_address varchar(255);
ALTER TABLE `Divisions` ADD COLUMN brainstorm_support char(1) DEFAULT 'N';

ALTER TABLE `Tracks` ADD COLUMN divisionid int;
ALTER TABLE `Tracks` ADD COLUMN `description` text;
ALTER TABLE `Tracks` ADD FOREIGN KEY (divisionid) REFERENCES `Divisions`(divisionid) ON DELETE SET NULL ON UPDATE CASCADE;

INSERT INTO PatchLog (patchname) VALUES ('61WIS_brainstorm_revisions.sql');
