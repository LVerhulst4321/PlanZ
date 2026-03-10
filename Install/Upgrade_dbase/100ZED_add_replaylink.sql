ALTER TABLE Sessions ADD COLUMN replaylink varchar(512) DEFAULT NULL AFTER signuplink;
INSERT INTO PatchLog (patchname) VALUES ('100ZED_add_replaylink');
