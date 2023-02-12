##
## Some cons want to print out the schedule for each (physical) room to
## to post outside of the door. This is a tool that some
##
## Created by BC Holmes
##

INSERT INTO `module`
(`name`, `package_name`, `description`, `is_enabled`)
values
('Print Room Schedule', 'planz.room_schedule',
'Provide a tool (in the Tools area) option that allows con staff to print a physical copy of the room schedule for posting beside a room''s door.', 1);

INSERT INTO PatchLog (patchname) VALUES ('93ZED_room_schedule_module.sql');