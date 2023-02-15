##
## Some cons want to assign simple session numbers to their
## sessions, so the first session of the con is number 1, etc.
##
## Created by BC Holmes
##

INSERT INTO `module`
(`name`, `package_name`, `description`, `is_enabled`)
values
('Assign Session Numbers', 'planz.session_number',
'Provide the ability to assign simple session numbers to scheduled sessions, typically in schedule order.', 0);

INSERT INTO PatchLog (patchname) VALUES ('94ZED_session_number_module.sql');