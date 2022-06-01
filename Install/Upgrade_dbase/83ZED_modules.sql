## 
## Create a basic notion of modules.
##
## Created by BC Holmes
##

CREATE TABLE `module` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(64) NOT NULL,
  `package_name` varchar(255) NOT NULL DEFAULT 0,
  `description` text,
  `is_enabled` tinyint NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `module` 
(`name`, `package_name`, `description`, `is_enabled`)
values 
('Volunteering Module', 'planz.volunteering', 
'The volunteering module allows users to create volunteer shifts, and allows participants to sign up for those shifts', 0);

ALTER TABLE `Phases` 
  ADD COLUMN `module_id` int(11) REFERENCES `module`(id) ON DELETE CASCADE; 

UPDATE `Phases` SET module_id = (select max(id) from `module`) WHERE `phasename` in ('Volunteer Sign-up', 'Volunteer Set-up');

insert into `PermissionAtoms` (permatomtag, page, notes)
values ('AdminModules', 'Admin Modules', 'Enables admin folks to activate modules');

insert into `Permissions` (permatomid, phaseid, permroleid)
select max(permatomid), null, 1 from PermissionAtoms;


INSERT INTO PatchLog (patchname) VALUES ('83ZED_modules.sql');