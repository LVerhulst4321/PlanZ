## This script adds tech streaming and recording permissions for participants.
##
##	Created by James Shields on 2024-01-10
## 	Copyright (c) 2020 by Peter Olszowka. All rights reserved. See copyright document for more details.
##

ALTER TABLE Participants
    ADD column allow_recording tinyint(1) AFTER use_photo,
    ADD column allow_streaming tinyint(1) AFTER use_photo;

UPDATE Participants SET share_email = 2 where share_email =0;
UPDATE Participants SET use_photo = 2 where use_photo =0;

INSERT INTO PermissionAtoms(permatomid, permatomtag, page, notes)
VALUES
    (2066, 'EditUserPermissions', 'Administer Participants', 'enables editing of permissions like interested in participating, recording, streaming, etc');

INSERT INTO Permissions(permatomid, phaseid, permroleid)
SELECT a.permatomid, null, r.permroleid
FROM PermissionAtoms a
JOIN PermissionRoles r ON (r.permrolename IN ('Administrator', 'Senior Staff'))
WHERE permatomtag IN ('EditUserPermissions');

INSERT INTO PatchLog (patchname) VALUES ('99ZED_add_streaming_and_recording.sql');
