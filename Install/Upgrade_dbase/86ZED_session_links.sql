## This script adds additional link types for sessions
##
##	Created by James Shields on 2022-10-06
## 	Copyright (c) 2020 by Peter Olszowka. All rights reserved. See copyright document for more details.
##
ALTER TABLE Sessions
ADD streaminglink VARCHAR(512) AFTER meetinglink;

ALTER TABLE Sessions
ADD signuplink VARCHAR(512) AFTER streaminglink;

INSERT INTO PatchLog (patchname) VALUES ('86ZED_session_links.sql');
