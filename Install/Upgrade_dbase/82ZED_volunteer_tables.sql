## This script adds tables necessary to support
## volunteer jobs and shifts
##
## Created by BC Holmes
##

CREATE TABLE `volunteer_job` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `job_name` varchar(64) NOT NULL,
  `is_online` tinyint NOT NULL DEFAULT 0,
  `job_description` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


insert into `Phases`
(phasename, current, notes, implemented, display_order)
select
'Volunteer Sign-up', 0, 'Add on Volunteering', 1, max(display_order) + 10
from `Phases`;

insert into `PermissionAtoms` (permatomtag, page, notes)
values ('Volunteering', 'many', 'Enables sign up for volunteer shifts');

insert into `Permissions` (permatomid, phaseid, permroleid)
select max(permatomid), max(phaseid), 3
 from PermissionAtoms, Phases;

insert into `Phases`
(phasename, current, notes, implemented, display_order)
select
'Volunteer Set-up', 0, 'Staff Volunteering Set-up', 1, max(display_order) + 10
from `Phases`;

insert into `PermissionAtoms` (permatomtag, page, notes)
values ('Volunteering Set-up', 'many', 'Enables staff to set up volunteering schedules');

insert into `PermissionRoles` (permrolename, notes, display_order)
select 'Volunteer Coordinator', 'Defines volunteer jobs and shifts', max(display_order) + 10
from `PermissionRoles`;

insert into `Permissions` (permatomid, phaseid, permroleid)
select max(permatomid), max(phaseid), max(permroleid) 
from PermissionAtoms, Phases, PermissionRoles;


CREATE TABLE `volunteer_shift` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `volunteer_job_id` int(11) NOT NULL,
  `min_volunteer_count` int(11) NOT NULL,
  `max_volunteer_count` int(11) NOT NULL,
  `from_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `to_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_volunteer_shift_to_volunteer_job` FOREIGN KEY (`volunteer_job_id`) REFERENCES `volunteer_job` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


