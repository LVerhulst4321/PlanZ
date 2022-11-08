## This script adds new table for participant links and link types.
##
##  Created by James Shields on 2022-03-21
##

DROP TABLE IF EXISTS `participant_link`;

DROP TABLE IF EXISTS `link_type`;

CREATE TABLE `link_type` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `link_type_key` VARCHAR(10) NOT NULL,
  `link_type_name` VARCHAR(30) NOT NULL,
  `link_regex` VARCHAR(255) NOT NULL,
  `link_prefix` VARCHAR(255) NULL,
  `display_prefix` VARCHAR(255) NULL,
  `is_initial` TINYINT NOT NULL DEFAULT 0,
  `is_desc` TINYINT NOT NULL DEFAULT 0,
  `display_order` INT(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `participant_link` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `badge_id` VARCHAR(15) NOT NULL DEFAULT '' COMMENT 'New foreign key naming convention',
  `link_type_id` INT(11) NOT NULL,
  `link_value` VARCHAR(255) NOT NULL,
  `link_desc` VARCHAR(255) NULL,
  `updated_date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `is_approved` INT(11) NOT NULL DEFAULT 0,
  CONSTRAINT `participant_link_participant` FOREIGN KEY (`badge_id`) REFERENCES `Participants` (`badgeid`),
  CONSTRAINT `participant_link_link_type` FOREIGN KEY (`link_type_id`) REFERENCES `link_type` (`id`),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `link_type` (`link_type_key`, `link_type_name`, `is_initial`, `is_desc`, `display_order`, `link_prefix`, `display_prefix`, `link_regex`) VALUES
('WEB', 'Website', 1, 1, 0, '', '', '^http(s)?:\/\/\S+.\S+$'),
('TWT', 'Twitter', 1, 0, 5, 'https://twitter.com/', '@', '^\S+$'),
('FB', 'Facebook', 1, 0, 10, 'https://www.facebook.com/', 'https://www.facebook.com/', '^\S+$'),
('AMA', 'Amazon', 0, 0, 20, 'https://www.amazon.com/', 'https://www.amazon.com/', '^\S+$'),
('DIS', 'Discord', 0, 0, 20, 'https://discord.com/', 'https://discord.com/', '^\S+$'),
('GR', 'Goodreads', 0, 0, 25, 'https://www.goodreads.com/', 'https://www.goodreads.com/', '^\S+$'),
('IG', 'Instagram', 0, 0, 30, 'https://www.instagram.com/', 'https://www.instagram.com/', '^\S+$'),
('LI', 'LinkedIn', 0, 0, 35, 'https://www.linkedin.com/in/', 'https://www.linkedin.com/in/', '^\S+$'),
('TIK', 'TikTok', 0, 0, 40, 'https://www.tiktok.com/', 'https://www.tiktok.com/', '^\S+$'),
('TCH', 'Twitch', 0, 0, 40, 'https://www.twitch.tv/', 'https://www.twitch.tv/', '^\S+$'),
('YT', 'YouTube', 0, 0, 45, 'https://www.youtube.com/', 'https://www.youtube.com/', '^\S+$');





## Set up permissions.
INSERT INTO `PermissionAtoms` (`permatomid`, `permatomtag`, `page`, `notes`)
VALUES ('2065', 'ce_link_type', 'Edit Configuration Tables', 'enables edit');

INSERT INTO Permissions(permatomid, phaseid, permroleid, badgeid)
SELECT a.permatomid, null, r.permroleid, null 
FROM PermissionAtoms a
JOIN PermissionRoles r ON (r.permrolename = 'Administrator')
WHERE permatomtag IN  ('ce_link_type');



INSERT INTO PatchLog (patchname) VALUES ('89ZED_add_participant_link.sql');