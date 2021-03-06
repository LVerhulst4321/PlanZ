## This script adds permission atoms for table editing and does default assignment to admin of those atoms
##
##    Created by Syd Weinstein on January 9,2021
##     Copyright (c) 2021 by Peter Olszowka. All rights reserved. See copyright document for more details.
##

INSERT INTO PermissionAtoms(permatomid, permatomtag, page, notes)
VALUES
    (2000, 'ce_All', 'Edit Configuration Tables', 'enables edit'),
    (2001, 'ce_BioEditStatuses', 'Edit Configuration Tables', 'enables edit'),
    (2002, 'ce_Credentials', 'Edit Configuration Tables', 'enables edit'),
    (2003, 'ce_Divisions', 'Edit Configuration Tables', 'enables edit'),
    (2004, 'ce_EmailCC', 'Edit Configuration Tables', 'enables edit'),
    (2005, 'ce_EmailFrom', 'Edit Configuration Tables', 'enables edit'),
    (2006, 'ce_EmailTo', 'Edit Configuration Tables', 'enables edit'),
    (2007, 'ce_Features', 'Edit Configuration Tables', 'enables edit'),
    (2008, 'ce_KidsCategories', 'Edit Configuration Tables', 'enables edit'),
    (2009, 'ce_LanguageStatuses', 'Edit Configuration Tables', 'enables edit'),
    (2010, 'ce_PubStatuses', 'Edit Configuration Tables', 'enables edit'),
    (2011, 'ce_RegTypes', 'Edit Configuration Tables', 'enables edit'),
    (2012, 'ce_Roles', 'Edit Configuration Tables', 'enables edit'),
    (2013, 'ce_RoomHasSet', 'Edit Configuration Tables', 'enables edit'),
    (2014, 'ce_Rooms', 'Edit Configuration Tables', 'enables edit'),
    (2015, 'ce_RoomSets', 'Edit Configuration Tables', 'enables edit'),
    (2016, 'ce_Services', 'Edit Configuration Tables', 'enables edit'),
    (2017, 'ce_SessionStatuses', 'Edit Configuration Tables', 'enables edit'),
    (2018, 'ce_Tags', 'Edit Configuration Tables', 'enables edit'),
    (2019, 'ce_Times', 'Edit Configuration Tables', 'enables edit'),
    (2020, 'ce_Tracks', 'Edit Configuration Tables', 'enables edit'),
    (2021, 'ce_Types', 'Edit Configuration Tables', 'enables edit');

INSERT INTO Permissions(permatomid, phaseid, permroleid)
SELECT a.permatomid, null, r.permroleid 
FROM PermissionAtoms a
JOIN PermissionRoles r ON (r.permrolename = 'Administrator')
WHERE permatomtag IN ('ce_All', 'ce_BioEditStatuses', 'ce_Credentials',
    'ce_Divisions', 'ce_EmailCC', 'ce_EmailFrom', 'ce_EmailTo',
    'ce_Features', 'ce_KidsCategories', 'ce_LanguageStatuses',
    'ce_PubStatuses', 'ce_RegTypes', 'ce_Roles', 'ce_RoomHasSet',
    'ce_Rooms', 'ce_RoomSets', 'ce_Services', 'ce_SessionStatuses',
    'ce_Tags', 'ce_Times', 'ce_Tracks', 'ce_Types');

ALTER TABLE RegTypes RENAME RegTypes_obsolete;

CREATE TABLE RegTypes (
  regtypeid int NOT NULL AUTO_INCREMENT,
  display_order int NULL DEFAULT 0,
  regtype varchar(40) NOT NULL DEFAULT '',
  message varchar(100) DEFAULT NULL,
  PRIMARY KEY (regtypeid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO RegTypes(regtype, message)
SELECT regtype, message 
FROM RegTypes_obsolete;

CREATE UNIQUE INDEX RegTypes_Regtype
ON RegTypes(regtype);

INSERT INTO PatchLog (patchname) VALUES ('59_config_edit_tables.sql');
