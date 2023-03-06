##
## Optional PlanZ module to extract session data for OBS.
##
## Created by James
##

INSERT INTO `module`
(`name`, `package_name`, `description`, `is_enabled`)
values
('OBS Feeds', 'planz.obs_feed',
'Create extract of sessions per day/room for OBS.', 0);

INSERT INTO PatchLog (patchname) VALUES ('96ZED_obs_feed_module.sql');
