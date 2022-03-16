## This script adds new table for locations.
##   Also adds config table permissions.
##
##  Created by Leane Verhulst on August 26, 2021
##

CREATE TABLE `Locations` (
  `locationid` int(11) NOT NULL AUTO_INCREMENT,
  `locationname` varchar(30) NOT NULL,
  `locationhours` text DEFAULT NULL,
  `display_order` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`locationid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


## Set up permissions.
INSERT INTO `PermissionAtoms` (`permatomid`, `permatomtag`, `page`, `notes`)
VALUES ('2055', 'ce_Locations', 'Edit Configuration Tables', 'enables edit');

INSERT INTO Permissions(permatomid, phaseid, permroleid, badgeid)
SELECT a.permatomid, null, r.permroleid, null 
FROM PermissionAtoms a
JOIN PermissionRoles r ON (r.permrolename = 'Administrator')
WHERE permatomtag IN  ('ce_Locations');


INSERT INTO PatchLog (patchname) VALUES ('72ZED_locations.sql');
