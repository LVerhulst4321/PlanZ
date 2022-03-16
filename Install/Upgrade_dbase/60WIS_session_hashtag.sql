## This script adds a field to Sessions
##
## Created by BC Holmes
##

ALTER TABLE Sessions
    ADD COLUMN hashtag VARCHAR(50) DEFAULT NULL;

INSERT INTO PatchLog (patchname) VALUES ('60WIS_session_hashtag.sql');