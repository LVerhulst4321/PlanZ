## This script adds missing roomname column to locations table created by patch 72.
## Checks if column exists before adding, as some dump files include column.
##
##  Created by James Shields on 2022-03-31
##

DROP PROCEDURE IF EXISTS `?`;
DELIMITER //
CREATE PROCEDURE `?`()
BEGIN
    IF NOT EXISTS(
            SELECT * 
            FROM information_schema.COLUMNS 
            WHERE 
                TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = 'Locations' 
            AND COLUMN_NAME = 'roomname') THEN
        ALTER TABLE `Locations`
            ADD COLUMN `roomname` varchar(30) DEFAULT NULL AFTER `locationname`;
    END IF;
END //
DELIMITER ;
CALL `?`();
DROP PROCEDURE `?`;

INSERT INTO PatchLog (patchname) VALUES ('81ZED_locations_roomname.sql');
