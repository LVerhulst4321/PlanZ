## This script adds permission atoms for table editing of brainstorm tables and does default assignment to admin of those atoms
##
##    Created by James Shields on 2022-10-19
##     Copyright (c) 2021 by Peter Olszowka. All rights reserved. See copyright document for more details.
##

INSERT INTO PermissionAtoms(permatomid, permatomtag, page, notes)
VALUES
    (2062, 'ce_perennial_con_info', 'Edit Configuration Tables', 'enables edit'),
    (2063, 'ce_con_info', 'Edit Configuration Tables', 'enables edit'),
    (2064, 'ce_con_key_dates', 'Edit Configuration Tables', 'enables edit');

INSERT INTO Permissions(permatomid, phaseid, permroleid)
SELECT a.permatomid, null, r.permroleid 
FROM PermissionAtoms a
JOIN PermissionRoles r ON (r.permrolename = 'Administrator')
WHERE permatomtag IN ('ce_perennial_con_info', 'ce_con_info', 'ce_con_key_dates');

ALTER TABLE perennial_con_info ADD display_order INT NULL AFTER name;
ALTER TABLE con_info ADD display_order INT NULL AFTER name;
ALTER TABLE con_key_dates ADD display_order INT NULL AFTER con_id;

INSERT INTO PatchLog (patchname) VALUES ('88ZED_brainstorm_ce_permissions.sql');
