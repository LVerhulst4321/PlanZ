## This script adds tables necessary to support
## volunteer jobs and shifts
##
## Created by BC Holmes
##

CREATE TABLE `volunteer_job` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `job_name` varchar(64) NOT NULL,
  `is_online` tinyint NOT NULL DEFAULT 0,
  `job_description` text
  PRIMARY KEY (`id`),
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


insert into `Phases`
(phasename, current, notes, implemented, display_order)
select
'Volunteer Sign-up', 0, 'Add on Volunteering', 1, max(display_order) + 10
from `Phases`;

insert into `PermissionAtoms` (permatomtag, page, notes)
values ('Volunteering', 'many', 'Enables sign up for volunteer shifts');

insert into `Permissions` (permatomid, phaseid, permroleid)
values (select max(permatomid) from PermissionAtoms), (select max(phaseid) from Phases), 3);