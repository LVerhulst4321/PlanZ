##
## Optional PlanZ module to print table tents.
##
## Created by James
##

INSERT INTO `module`
(`name`, `package_name`, `description`, `is_enabled`)
values
('Table Tents', 'table_tents.table_tents',
'Module for printing table tents.', 0);

INSERT INTO PatchLog (patchname) VALUES ('96ZED_table_tents_module.sql');
