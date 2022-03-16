## This script adds fields to CongoReg for registration system integration.
##
##  Created by Leane Verhulst on August 20, 2021
##

## Add new fields for registration integration
ALTER TABLE CongoDump ADD COLUMN `badgenumber` int(4) DEFAULT NULL AFTER `regtype`;
ALTER TABLE CongoDump ADD COLUMN `last_login` timestamp NOT NULL DEFAULT current_timestamp() AFTER `badgenumber`;
ALTER TABLE CongoDump ADD COLUMN `alternatebadgeid` varchar(15) DEFAULT NULL AFTER `last_login`;
ALTER TABLE CongoDump ADD COLUMN `last_reg_update` timestamp NOT NULL DEFAULT current_timestamp() AFTER `alternatebadgeid`;


INSERT INTO PatchLog (patchname) VALUES ('64ZED_add_reg_fields.sql');
