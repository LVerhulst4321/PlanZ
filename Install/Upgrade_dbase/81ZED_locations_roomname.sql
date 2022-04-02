## This script adds missing roomname column to locations table created by patch 72.
##
##  Created by James Shields on 2022-03-31
##

ALTER TABLE `Locations`
  ADD COLUMN `roomname` varchar(30) DEFAULT NULL AFTER `locationname`;

INSERT INTO PatchLog (patchname) VALUES ('81ZED_locations_roomname.sql');
