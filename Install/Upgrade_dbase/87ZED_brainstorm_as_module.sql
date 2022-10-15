## 
## Brainstorm is a not-frequently used feature. Treat it as a module
##
## Created by BC Holmes
##

INSERT INTO `module` 
(`name`, `package_name`, `description`, `is_enabled`)
values 
('Brainstorm', 'planz.brainstorm', 
'The brainstorm feature can be used to allow the con''s attendees to suggest sessions.', 1);

UPDATE `PermissionAtoms` SET module_id = (select max(id) from `module`) WHERE `permatomtag` in ('BrainstormSubmit');

UPDATE `Phases` SET module_id = (select max(id) from `module`) WHERE `phasename` = 'Brainstorm';

# remove the "reg_" prefix from con info tables, as that prefix was intended to reference registration.

DROP VIEW current_con;

RENAME TABLE reg_con_info to con_info;
RENAME TABLE reg_perennial_con_info to perennial_con_info;

CREATE VIEW current_con AS
    SELECT c.id, c.name, p.name AS perennial_name, c.con_start_date, c.con_end_date, p.website_url
      FROM con_info c, perennial_con_info p
    WHERE c.perennial_con_id = p.id
    AND c.active_to_time > now()
    AND c.active_from_time <= now();

INSERT INTO PatchLog (patchname) VALUES ('87ZED_brainstorm_as_module.sql');