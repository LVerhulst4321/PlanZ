## This script adds new table for service type and adds field to service table.
##   Also adds config table permissions.
##
##  Created by Leane Verhulst on August 23, 2021
##

CREATE TABLE `ServiceTypes` (
  `servicetypeid` int(11) NOT NULL AUTO_INCREMENT,
  `servicetypename` varchar(50) DEFAULT NULL,
  `display_order` int(11) DEFAULT NULL,
  PRIMARY KEY (`servicetypeid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `ServiceTypes` (`servicetypeid`, `servicetypename`, `display_order`) VALUES
(1, 'Programming', 1),
(2, 'AV', 2),
(3, 'Hotel', 3),
(100, 'Other', 100);


## Add new field and key for service type
ALTER TABLE `Services` ADD COLUMN `servicetypeid` int(11) NOT NULL AFTER `servicename`;
ALTER TABLE `Services` ADD KEY `servicetypeid` (`servicetypeid`);


## Set up permissions.
INSERT INTO `PermissionAtoms` (`permatomid`, `permatomtag`, `page`, `notes`)
VALUES ('2054', 'ce_ServiceTypes', 'Edit Configuration Tables', 'enables edit');

INSERT INTO Permissions(permatomid, phaseid, permroleid, badgeid)
SELECT a.permatomid, null, r.permroleid, null 
FROM PermissionAtoms a
JOIN PermissionRoles r ON (r.permrolename = 'Administrator')
WHERE permatomtag IN  ('ce_ServiceTypes');



INSERT INTO PatchLog (patchname) VALUES ('69ZED_service_type.sql');
