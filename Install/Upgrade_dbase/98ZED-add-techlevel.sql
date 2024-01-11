## This script adds tech level for sessions
##
##	Created by Andrew January on 2024-01-10
## 	Copyright (c) 2020 by Peter Olszowka. All rights reserved. See copyright document for more details.
##
CREATE TABLE `TechLevel` (
    `techlevelid` int(11) NOT NULL auto_increment,
    `techlevel` varchar(30) character set latin1 collate latin1_general_ci default NULL,
    `display_order` int(11) NOT NULL default '0',
    PRIMARY KEY  (`techlevelid`)
    ) ENGINE=InnoDB DEFAULT CHARSET=latin1;

Insert into TechLevel values
    ('1','Green','1'),
    ('2','Amber','2'),
    ('3','Red','3'),
    ('4','Grey','4'),
    ('99','Unspecified','99');
    
Alter table Sessions 
    add column techlevelid int(11) after streaminglink;
   
Update Sessions set techlevelid=99;

Alter table Sessions 
    modify column divisionid int(11) not null,
    add CONSTRAINT `Sessions_tlfk` FOREIGN KEY (`techlevelid`) REFERENCES `TechLevel` (`techlevelid`);

INSERT INTO PermissionAtoms(permatomid, permatomtag, page, notes)
VALUES
    (2065, 'ce_TechLevel', 'Edit TechLevels Tables', 'enables edit');

INSERT INTO Permissions(permatomid, phaseid, permroleid)
SELECT a.permatomid, null, r.permroleid 
FROM PermissionAtoms a
JOIN PermissionRoles r ON (r.permrolename = 'Administrator')
WHERE permatomtag IN ('ce_TechLevel');

INSERT INTO PatchLog (patchname) VALUES ('98ZED-add-techlevel.sql');
