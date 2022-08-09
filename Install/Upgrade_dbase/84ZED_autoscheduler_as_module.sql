## 
## Treat the autoscheduler as a module
##
## Created by BC Holmes
##

INSERT INTO `module` 
(`name`, `package_name`, `description`, `is_enabled`)
values 
('Auto-Scheduler', 'planz.autoscheduler', 
'The auto-scheduler attempts to find the optimal schedule using participants'' availability, room sizes, and other data.', 0);

insert into `PermissionAtoms` (permatomtag, page, notes)
values ('AutoScheduler', 'Auto Scheduler', 'Allows the programming team to run the auto-scheduler');

# Give the admin role autoscheduler privileges
insert into `Permissions` (permatomid, phaseid, permroleid)
select max(permatomid), null, 1 from PermissionAtoms;

UPDATE `PermissionAtoms` SET module_id = (select max(id) from `module`) WHERE `permatomtag` in ('AutoScheduler');

INSERT INTO PatchLog (patchname) VALUES ('84ZED_autoscheduler_as_module.sql');