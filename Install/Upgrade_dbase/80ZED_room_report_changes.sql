## This script makes changes for room reports.
##  - Remove divisionid from the Rooms table.
##  - Add new table for grouping rooms for reports.
##
## Created by Leane Verhulst
##

DROP PROCEDURE IF EXISTS `?`;
DELIMITER //
CREATE PROCEDURE `?`()
BEGIN
    IF EXISTS(
            SELECT * 
            FROM information_schema.COLUMNS 
            WHERE 
                TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = 'Rooms' 
            AND COLUMN_NAME = 'divisionid') THEN
        ALTER TABLE `Rooms` DROP FOREIGN KEY `Rooms_ibfk_2`;        ## Foreign key for divisionid
        ALTER TABLE `Rooms` DROP INDEX  `Rooms_ibfk_2`;             ## Index for divisionid
        ALTER TABLE `Rooms` DROP COLUMN `divisionid`;               ## When this column was added, no patchlog file was created.
    END IF;
END //
DELIMITER ;
CALL `?`();
DROP PROCEDURE `?`;

CREATE TABLE `room_report_group` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `room_report_group_name` varchar(100) DEFAULT NULL,
  `description` varchar(1024) DEFAULT NULL,
  `display_order` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `room_report_group_has_room` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `room_report_group_id` int(11) NOT NULL DEFAULT 0,
  `room_id` int(11) NOT NULL DEFAULT 0,
  `display_order` int(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `room_report_group_id` (`room_report_group_id`),
  KEY `room_id` (`room_id`),
  CONSTRAINT `FK__room_report_group_has_room__rooms` FOREIGN KEY (`room_id`) REFERENCES `Rooms` (`roomid`),
  CONSTRAINT `FK__room_report_group_has_room__room_report_group` FOREIGN KEY (`room_report_group_id`) REFERENCES `room_report_group` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


INSERT INTO `room_report_group` (`room_report_group_name`, `description`, `display_order`) VALUES
('Programming Rooms', 'These are rooms used for Programming panels.', 1),
('Event Rooms', 'These are rooms that have Event sessions.', 2),
('Filk Rooms', 'These are rooms that have Filk sessions.', 3),
('Virtual Rooms', 'These are the virtual rooms.', 4);




## Set up permissions.
INSERT INTO `PermissionAtoms` (`permatomid`, `permatomtag`, `page`, `notes`) VALUES 
('2056', 'ce_room_report_group', 'Edit Configuration Tables', 'enables edit'),
('2057', 'ce_room_report_group_has_room', 'Edit Configuration Tables', 'enables edit');

INSERT INTO Permissions(permatomid, phaseid, permroleid, badgeid)
SELECT a.permatomid, null, r.permroleid, null 
FROM PermissionAtoms a
JOIN PermissionRoles r ON (r.permrolename = 'Administrator')
WHERE permatomtag IN ('ce_room_report_group', 'ce_room_report_group_has_room');





INSERT INTO PatchLog (patchname) VALUES ('80ZED_room_report_changes.sql');