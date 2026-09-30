## This script converts the database and every existing table from utf8 (3-byte) to
## utf8mb4 (4-byte), so that free-text fields (e.g. My Availability's "prevent conflict"
## and "other constraints", participant bios, session descriptions, etc.) can actually
## store emoji and other characters outside the Basic Multilingual Plane, instead of
## the save failing.
##
## utf8mb4_general_ci is used (rather than utf8mb4_unicode_ci) because it is the direct
## 4-byte analog of utf8_general_ci, which every table already uses: it keeps existing
## sorting/comparison behavior unchanged, so this is a storage-width change only, not a
## collation-behavior change.
##
## Every foreign key in the schema is dropped before the charset conversion and
## recreated afterward. This is necessary, not just cautious: on MySQL 8.0,
## ALTER TABLE ... CONVERT TO CHARACTER SET on a column used in a foreign key fails
## with error 1832 ("Cannot change column ... used in a foreign key constraint") even
## with FOREIGN_KEY_CHECKS=0 — that setting only suppresses referential-integrity
## validation, not this separate metadata check MySQL 8.0 added. MariaDB (used in dev)
## doesn't enforce this, which is why this didn't surface during development and only
## showed up running this against a MySQL 8 database.
##
## The DROP/ADD FOREIGN KEY statements below are generated from this project's actual
## schema (captured via information_schema, not hand-typed) rather than looped
## dynamically in a stored procedure, even though that would be more resilient to
## schema drift from Install/EmptyDbase.dump: some hosts (particularly shared/managed
## MySQL hosting) restrict database users from CREATE PROCEDURE / mysql.proc access
## even when they otherwise have full DDL rights, which surfaces as "ERROR 1728: Cannot
## load from mysql.proc" — not a real corruption, just a permissions wall. Flat SQL
## avoids that dependency entirely, at the cost of needing to be regenerated if the
## schema's foreign keys change before this patch is applied everywhere it needs to run.
##
## Note: this changes storage/comparison behavior only. The application's connection
## charset (mysqli_set_charset() in webpages/db_functions.php) also needs to move from
## "utf8" to "utf8mb4" for the app to actually be able to send/receive 4-byte characters
## over the connection — that is a separate code change, tracked alongside this patch.
##
## Created by James Shields on 2026-08-25
## Copyright (c) 2020 by Peter Olszowka. All rights reserved. See copyright document for more details.
##

SET FOREIGN_KEY_CHECKS=0;

-- Drop every foreign key in the schema (grouped per table) so the charset conversion
-- below can run without MySQL 8.0's error 1832.
ALTER TABLE `CongoDumpHistory` DROP FOREIGN KEY `CongoDumpHistory_ibfk_1`, DROP FOREIGN KEY `CongoDumpHistory_ibfk_2`, DROP FOREIGN KEY `CongoDumpHistory_ibfk_3`;
ALTER TABLE `ParticipantAvailability` DROP FOREIGN KEY `ParticipantAvailability_ibfk_1`;
ALTER TABLE `ParticipantAvailabilityDays` DROP FOREIGN KEY `ParticipantAvailabilityDays_ibfk_1`;
ALTER TABLE `ParticipantAvailabilityTimes` DROP FOREIGN KEY `ParticipantAvailabilityTimes_ibfk_1`;
ALTER TABLE `ParticipantDetails` DROP FOREIGN KEY `ParticipantDetails_ibfk_1`, DROP FOREIGN KEY `ParticipantDetails_ibfk_2`, DROP FOREIGN KEY `ParticipantDetails_ibfk_3`;
ALTER TABLE `ParticipantHasCredential` DROP FOREIGN KEY `phcfk1`, DROP FOREIGN KEY `phcfk2`;
ALTER TABLE `ParticipantHasInterest` DROP FOREIGN KEY `phifk1`, DROP FOREIGN KEY `phifk2`;
ALTER TABLE `ParticipantHasRole` DROP FOREIGN KEY `ParticipantHasRole_ibfk_1`, DROP FOREIGN KEY `ParticipantHasRole_ibfk_2`;
ALTER TABLE `ParticipantInterests` DROP FOREIGN KEY `ParticipantInterests_ibfk_1`;
ALTER TABLE `ParticipantOnSession` DROP FOREIGN KEY `ParticipantOnSession_ibfk_1`, DROP FOREIGN KEY `ParticipantOnSession_ibfk_2`;
ALTER TABLE `ParticipantSessionInterest` DROP FOREIGN KEY `ParticipantSessionInterest_ibfk_1`, DROP FOREIGN KEY `ParticipantSessionInterest_ibfk_2`, DROP FOREIGN KEY `ParticipantSessionInterest_ibfk_3`, DROP FOREIGN KEY `ParticipantSessionInterest_ibfk_4`;
ALTER TABLE `ParticipantSuggestions` DROP FOREIGN KEY `ParticipantSuggestions_ibfk_1`;
ALTER TABLE `ParticipantSurveyAnswers` DROP FOREIGN KEY `ParticipantSurveyAnswers_ibfk_1`, DROP FOREIGN KEY `ParticipantSurveyAnswers_ibfk_2`;
ALTER TABLE `Participants` DROP FOREIGN KEY `Participants_photodeny`, DROP FOREIGN KEY `participantphotostatus_fk`;
ALTER TABLE `Permissions` DROP FOREIGN KEY `Permissions_ibfk_1`, DROP FOREIGN KEY `Permissions_ibfk_2`, DROP FOREIGN KEY `Permissions_ibfk_3`;
ALTER TABLE `PreviousConTracks` DROP FOREIGN KEY `PreviousCons_ibfk_1`;
ALTER TABLE `PreviousSessions` DROP FOREIGN KEY `PreviousSessions_ibfk_1`, DROP FOREIGN KEY `PreviousSessions_ibfk_2`, DROP FOREIGN KEY `PreviousSessions_ibfk_3`, DROP FOREIGN KEY `PreviousSessions_ibfk_4`, DROP FOREIGN KEY `PreviousSessions_ibfk_5`, DROP FOREIGN KEY `PreviousSessions_ibfk_6`, DROP FOREIGN KEY `PreviousSessions_ibfk_7`;
ALTER TABLE `RoomHasSet` DROP FOREIGN KEY `RoomHasSet_ibfk_1`, DROP FOREIGN KEY `RoomHasSet_ibfk_2`;
ALTER TABLE `Rooms` DROP FOREIGN KEY `Rooms_ibfk_1`;
ALTER TABLE `Schedule` DROP FOREIGN KEY `Schedule_ibfk_1`, DROP FOREIGN KEY `Schedule_ibfk_2`;
ALTER TABLE `SessionEditHistory` DROP FOREIGN KEY `SessionEditHistory_ibfk_1`, DROP FOREIGN KEY `SessionEditHistory_ibfk_2`, DROP FOREIGN KEY `SessionEditHistory_ibfk_3`, DROP FOREIGN KEY `SessionEditHistory_ibfk_4`;
ALTER TABLE `SessionHasFeature` DROP FOREIGN KEY `SessionHasFeature_ibfk_1`, DROP FOREIGN KEY `SessionHasFeature_ibfk_2`;
ALTER TABLE `SessionHasService` DROP FOREIGN KEY `SessionHasService_ibfk_1`, DROP FOREIGN KEY `SessionHasService_ibfk_2`;
ALTER TABLE `SessionHasTag` DROP FOREIGN KEY `Fkey1`, DROP FOREIGN KEY `Fkey2`;
ALTER TABLE `Sessions` DROP FOREIGN KEY `Sessions_ibfk_1`, DROP FOREIGN KEY `Sessions_ibfk_2`, DROP FOREIGN KEY `Sessions_ibfk_3`, DROP FOREIGN KEY `Sessions_ibfk_4`, DROP FOREIGN KEY `Sessions_ibfk_5`, DROP FOREIGN KEY `Sessions_ibfk_6`, DROP FOREIGN KEY `Sessions_ibfk_7`, DROP FOREIGN KEY `Sessions_ibfk_8`, DROP FOREIGN KEY `Sessions_tlfk`;
ALTER TABLE `SurveyQuestionConfig` DROP FOREIGN KEY `SurveyQuestionConfig_ibfk_1`;
ALTER TABLE `SurveyQuestionOptionConfig` DROP FOREIGN KEY `SurveyQuestionOptionConfig_ibfk_1`;
ALTER TABLE `SurveyQuestionTypeDefaults` DROP FOREIGN KEY `SurveyQuestionTypeDefaults_ibfk_1`;
ALTER TABLE `TrackCompatibility` DROP FOREIGN KEY `TrackCompatibility_ibfk_1`, DROP FOREIGN KEY `TrackCompatibility_ibfk_2`;
ALTER TABLE `Tracks` DROP FOREIGN KEY `Tracks_ibfk_1`;
ALTER TABLE `UserHasPermissionRole` DROP FOREIGN KEY `UserHasPermissionRole_ibfk_1`, DROP FOREIGN KEY `UserHasPermissionRole_ibfk_2`;
ALTER TABLE `con_info` DROP FOREIGN KEY `con_info_ibfk_1`;
ALTER TABLE `participant_has_volunteer_shift` DROP FOREIGN KEY `fk_participant_has_shift_to_shift`, DROP FOREIGN KEY `fk_participant_to_participant_has_shift`;
ALTER TABLE `participant_on_session_history` DROP FOREIGN KEY `participant_on_session_history_ibfk_1`, DROP FOREIGN KEY `participant_on_session_history_ibfk_2`, DROP FOREIGN KEY `participant_on_session_history_ibfk_3`;
ALTER TABLE `room_report_group_has_room` DROP FOREIGN KEY `FK__room_report_group_has_room__room_report_group`, DROP FOREIGN KEY `FK__room_report_group_has_room__rooms`;
ALTER TABLE `volunteer_shift` DROP FOREIGN KEY `fk_volunteer_shift_con_id`, DROP FOREIGN KEY `fk_volunteer_shift_to_volunteer_job`;

ALTER DATABASE CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

ALTER TABLE `AgeRanges` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE `BioEditStatuses` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE `CongoDump` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE `CongoDumpHistory` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE `Credentials` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE `CustomText` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE `Divisions` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE `EmailCC` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE `EmailFrom` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE `EmailHistory` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE `EmailQueue` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE `EmailTo` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE `Features` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE `Interests` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE `KidsCategories` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE `LanguageStatuses` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE `Locations` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE `ParticipantAvailability` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE `ParticipantAvailabilityDays` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE `ParticipantAvailabilityTimes` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE `ParticipantDetails` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE `ParticipantHasCredential` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE `ParticipantHasInterest` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE `ParticipantHasRole` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE `ParticipantInterests` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE `ParticipantOnSession` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE `ParticipantPasswordResetRequests` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE `ParticipantSessionInterest` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE `ParticipantSuggestions` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE `ParticipantSurveyAnswers` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE `Participants` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE `PatchLog` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE `PermissionAtoms` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE `PermissionRoles` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE `Permissions` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE `Phases` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE `PhotoDenialReasons` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE `PhotoUploadStatus` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE `PreviousConTracks` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE `PreviousParticipants` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE `PreviousSessions` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE `Pronouns` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE `PubStatuses` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE `RegTypes` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE `Roles` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE `RoomColors` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE `RoomHasSet` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE `RoomSets` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE `Rooms` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE `Schedule` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE `ServiceTypes` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE `Services` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE `SessionEditCodes` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE `SessionEditHistory` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE `SessionHasFeature` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE `SessionHasService` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE `SessionHasTag` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE `SessionStatuses` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE `Sessions` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE `SurveyQuestionConfig` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE `SurveyQuestionOptionConfig` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE `SurveyQuestionTypeDefaults` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE `SurveyQuestionTypes` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE `Tags` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE `TechLevel` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE `Times` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE `TrackCompatibility` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE `Tracks` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE `Types` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE `UserHasPermissionRole` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE `con_info` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE `con_key_dates` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE `module` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE `participant_has_volunteer_shift` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE `participant_on_session_history` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE `perennial_con_info` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE `room_availability_schedule` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE `room_availability_slot` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE `room_report_group` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE `room_report_group_has_room` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE `room_to_availability` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE `volunteer_job` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE `volunteer_shift` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

-- Recreate every foreign key exactly as it was, grouped per table.
ALTER TABLE `CongoDumpHistory` ADD CONSTRAINT `CongoDumpHistory_ibfk_1` FOREIGN KEY (`badgeid`) REFERENCES `Participants` (`badgeid`), ADD CONSTRAINT `CongoDumpHistory_ibfk_2` FOREIGN KEY (`createdbybadgeid`) REFERENCES `Participants` (`badgeid`), ADD CONSTRAINT `CongoDumpHistory_ibfk_3` FOREIGN KEY (`inactivatedbybadgeid`) REFERENCES `Participants` (`badgeid`);
ALTER TABLE `ParticipantAvailability` ADD CONSTRAINT `ParticipantAvailability_ibfk_1` FOREIGN KEY (`badgeid`) REFERENCES `Participants` (`badgeid`);
ALTER TABLE `ParticipantAvailabilityDays` ADD CONSTRAINT `ParticipantAvailabilityDays_ibfk_1` FOREIGN KEY (`badgeid`) REFERENCES `Participants` (`badgeid`);
ALTER TABLE `ParticipantAvailabilityTimes` ADD CONSTRAINT `ParticipantAvailabilityTimes_ibfk_1` FOREIGN KEY (`badgeid`) REFERENCES `Participants` (`badgeid`);
ALTER TABLE `ParticipantDetails` ADD CONSTRAINT `ParticipantDetails_ibfk_1` FOREIGN KEY (`badgeid`) REFERENCES `Participants` (`badgeid`), ADD CONSTRAINT `ParticipantDetails_ibfk_2` FOREIGN KEY (`agerangeid`) REFERENCES `AgeRanges` (`agerangeid`), ADD CONSTRAINT `ParticipantDetails_ibfk_3` FOREIGN KEY (`pronounid`) REFERENCES `Pronouns` (`pronounid`);
ALTER TABLE `ParticipantHasCredential` ADD CONSTRAINT `phcfk1` FOREIGN KEY (`badgeid`) REFERENCES `Participants` (`badgeid`), ADD CONSTRAINT `phcfk2` FOREIGN KEY (`credentialid`) REFERENCES `Credentials` (`credentialid`);
ALTER TABLE `ParticipantHasInterest` ADD CONSTRAINT `phifk1` FOREIGN KEY (`badgeid`) REFERENCES `Participants` (`badgeid`), ADD CONSTRAINT `phifk2` FOREIGN KEY (`interestid`) REFERENCES `Interests` (`interestid`);
ALTER TABLE `ParticipantHasRole` ADD CONSTRAINT `ParticipantHasRole_ibfk_1` FOREIGN KEY (`badgeid`) REFERENCES `Participants` (`badgeid`), ADD CONSTRAINT `ParticipantHasRole_ibfk_2` FOREIGN KEY (`roleid`) REFERENCES `Roles` (`roleid`);
ALTER TABLE `ParticipantInterests` ADD CONSTRAINT `ParticipantInterests_ibfk_1` FOREIGN KEY (`badgeid`) REFERENCES `Participants` (`badgeid`);
ALTER TABLE `ParticipantOnSession` ADD CONSTRAINT `ParticipantOnSession_ibfk_1` FOREIGN KEY (`badgeid`) REFERENCES `Participants` (`badgeid`), ADD CONSTRAINT `ParticipantOnSession_ibfk_2` FOREIGN KEY (`sessionid`) REFERENCES `Sessions` (`sessionid`);
ALTER TABLE `ParticipantSessionInterest` ADD CONSTRAINT `ParticipantSessionInterest_ibfk_1` FOREIGN KEY (`badgeid`) REFERENCES `Participants` (`badgeid`), ADD CONSTRAINT `ParticipantSessionInterest_ibfk_2` FOREIGN KEY (`sessionid`) REFERENCES `Sessions` (`sessionid`), ADD CONSTRAINT `ParticipantSessionInterest_ibfk_3` FOREIGN KEY (`badgeid`) REFERENCES `Participants` (`badgeid`), ADD CONSTRAINT `ParticipantSessionInterest_ibfk_4` FOREIGN KEY (`sessionid`) REFERENCES `Sessions` (`sessionid`);
ALTER TABLE `ParticipantSuggestions` ADD CONSTRAINT `ParticipantSuggestions_ibfk_1` FOREIGN KEY (`badgeid`) REFERENCES `Participants` (`badgeid`);
ALTER TABLE `ParticipantSurveyAnswers` ADD CONSTRAINT `ParticipantSurveyAnswers_ibfk_1` FOREIGN KEY (`participantid`) REFERENCES `Participants` (`badgeid`) ON DELETE CASCADE, ADD CONSTRAINT `ParticipantSurveyAnswers_ibfk_2` FOREIGN KEY (`questionid`) REFERENCES `SurveyQuestionConfig` (`questionid`) ON DELETE CASCADE;
ALTER TABLE `Participants` ADD CONSTRAINT `Participants_photodeny` FOREIGN KEY (`photodenialreasonid`) REFERENCES `PhotoDenialReasons` (`photodenialreasonid`), ADD CONSTRAINT `participantphotostatus_fk` FOREIGN KEY (`photouploadstatus`) REFERENCES `PhotoUploadStatus` (`photouploadstatus`);
ALTER TABLE `Permissions` ADD CONSTRAINT `Permissions_ibfk_1` FOREIGN KEY (`permatomid`) REFERENCES `PermissionAtoms` (`permatomid`), ADD CONSTRAINT `Permissions_ibfk_2` FOREIGN KEY (`phaseid`) REFERENCES `Phases` (`phaseid`), ADD CONSTRAINT `Permissions_ibfk_3` FOREIGN KEY (`permroleid`) REFERENCES `PermissionRoles` (`permroleid`);
ALTER TABLE `PreviousConTracks` ADD CONSTRAINT `PreviousCons_ibfk_1` FOREIGN KEY (`previousconid`) REFERENCES `con_info` (`id`);
ALTER TABLE `PreviousSessions` ADD CONSTRAINT `PreviousSessions_ibfk_1` FOREIGN KEY (`previousconid`) REFERENCES `con_info` (`id`), ADD CONSTRAINT `PreviousSessions_ibfk_2` FOREIGN KEY (`previousconid`,`previoustrackid`) REFERENCES `PreviousConTracks` (`previousconid`,`previoustrackid`), ADD CONSTRAINT `PreviousSessions_ibfk_3` FOREIGN KEY (`previousstatusid`) REFERENCES `SessionStatuses` (`statusid`), ADD CONSTRAINT `PreviousSessions_ibfk_4` FOREIGN KEY (`typeid`) REFERENCES `Types` (`typeid`), ADD CONSTRAINT `PreviousSessions_ibfk_5` FOREIGN KEY (`divisionid`) REFERENCES `Divisions` (`divisionid`), ADD CONSTRAINT `PreviousSessions_ibfk_6` FOREIGN KEY (`languagestatusid`) REFERENCES `LanguageStatuses` (`languagestatusid`), ADD CONSTRAINT `PreviousSessions_ibfk_7` FOREIGN KEY (`kidscatid`) REFERENCES `KidsCategories` (`kidscatid`);
ALTER TABLE `RoomHasSet` ADD CONSTRAINT `RoomHasSet_ibfk_1` FOREIGN KEY (`roomid`) REFERENCES `Rooms` (`roomid`), ADD CONSTRAINT `RoomHasSet_ibfk_2` FOREIGN KEY (`roomsetid`) REFERENCES `RoomSets` (`roomsetid`);
ALTER TABLE `Rooms` ADD CONSTRAINT `Rooms_ibfk_1` FOREIGN KEY (`roomcolorid`) REFERENCES `RoomColors` (`roomcolorid`);
ALTER TABLE `Schedule` ADD CONSTRAINT `Schedule_ibfk_1` FOREIGN KEY (`sessionid`) REFERENCES `Sessions` (`sessionid`), ADD CONSTRAINT `Schedule_ibfk_2` FOREIGN KEY (`roomid`) REFERENCES `Rooms` (`roomid`);
ALTER TABLE `SessionEditHistory` ADD CONSTRAINT `SessionEditHistory_ibfk_1` FOREIGN KEY (`sessionid`) REFERENCES `Sessions` (`sessionid`), ADD CONSTRAINT `SessionEditHistory_ibfk_2` FOREIGN KEY (`badgeid`) REFERENCES `Participants` (`badgeid`), ADD CONSTRAINT `SessionEditHistory_ibfk_3` FOREIGN KEY (`sessioneditcode`) REFERENCES `SessionEditCodes` (`sessioneditcode`), ADD CONSTRAINT `SessionEditHistory_ibfk_4` FOREIGN KEY (`statusid`) REFERENCES `SessionStatuses` (`statusid`);
ALTER TABLE `SessionHasFeature` ADD CONSTRAINT `SessionHasFeature_ibfk_1` FOREIGN KEY (`sessionid`) REFERENCES `Sessions` (`sessionid`), ADD CONSTRAINT `SessionHasFeature_ibfk_2` FOREIGN KEY (`featureid`) REFERENCES `Features` (`featureid`);
ALTER TABLE `SessionHasService` ADD CONSTRAINT `SessionHasService_ibfk_1` FOREIGN KEY (`sessionid`) REFERENCES `Sessions` (`sessionid`), ADD CONSTRAINT `SessionHasService_ibfk_2` FOREIGN KEY (`serviceid`) REFERENCES `Services` (`serviceid`);
ALTER TABLE `SessionHasTag` ADD CONSTRAINT `Fkey1` FOREIGN KEY (`sessionid`) REFERENCES `Sessions` (`sessionid`), ADD CONSTRAINT `Fkey2` FOREIGN KEY (`tagid`) REFERENCES `Tags` (`tagid`);
ALTER TABLE `Sessions` ADD CONSTRAINT `Sessions_ibfk_1` FOREIGN KEY (`trackid`) REFERENCES `Tracks` (`trackid`), ADD CONSTRAINT `Sessions_ibfk_2` FOREIGN KEY (`typeid`) REFERENCES `Types` (`typeid`), ADD CONSTRAINT `Sessions_ibfk_3` FOREIGN KEY (`kidscatid`) REFERENCES `KidsCategories` (`kidscatid`), ADD CONSTRAINT `Sessions_ibfk_4` FOREIGN KEY (`roomsetid`) REFERENCES `RoomSets` (`roomsetid`), ADD CONSTRAINT `Sessions_ibfk_5` FOREIGN KEY (`statusid`) REFERENCES `SessionStatuses` (`statusid`), ADD CONSTRAINT `Sessions_ibfk_6` FOREIGN KEY (`pubstatusid`) REFERENCES `PubStatuses` (`pubstatusid`), ADD CONSTRAINT `Sessions_ibfk_7` FOREIGN KEY (`divisionid`) REFERENCES `Divisions` (`divisionid`), ADD CONSTRAINT `Sessions_ibfk_8` FOREIGN KEY (`languagestatusid`) REFERENCES `LanguageStatuses` (`languagestatusid`), ADD CONSTRAINT `Sessions_tlfk` FOREIGN KEY (`techlevelid`) REFERENCES `TechLevel` (`techlevelid`);
ALTER TABLE `SurveyQuestionConfig` ADD CONSTRAINT `SurveyQuestionConfig_ibfk_1` FOREIGN KEY (`typeid`) REFERENCES `SurveyQuestionTypes` (`typeid`);
ALTER TABLE `SurveyQuestionOptionConfig` ADD CONSTRAINT `SurveyQuestionOptionConfig_ibfk_1` FOREIGN KEY (`questionid`) REFERENCES `SurveyQuestionConfig` (`questionid`) ON DELETE CASCADE;
ALTER TABLE `SurveyQuestionTypeDefaults` ADD CONSTRAINT `SurveyQuestionTypeDefaults_ibfk_1` FOREIGN KEY (`typeid`) REFERENCES `SurveyQuestionTypes` (`typeid`);
ALTER TABLE `TrackCompatibility` ADD CONSTRAINT `TrackCompatibility_ibfk_1` FOREIGN KEY (`previousconid`,`previoustrackid`) REFERENCES `PreviousConTracks` (`previousconid`,`previoustrackid`), ADD CONSTRAINT `TrackCompatibility_ibfk_2` FOREIGN KEY (`currenttrackid`) REFERENCES `Tracks` (`trackid`);
ALTER TABLE `Tracks` ADD CONSTRAINT `Tracks_ibfk_1` FOREIGN KEY (`divisionid`) REFERENCES `Divisions` (`divisionid`) ON UPDATE CASCADE ON DELETE SET NULL;
ALTER TABLE `UserHasPermissionRole` ADD CONSTRAINT `UserHasPermissionRole_ibfk_1` FOREIGN KEY (`badgeid`) REFERENCES `Participants` (`badgeid`), ADD CONSTRAINT `UserHasPermissionRole_ibfk_2` FOREIGN KEY (`permroleid`) REFERENCES `PermissionRoles` (`permroleid`);
ALTER TABLE `con_info` ADD CONSTRAINT `con_info_ibfk_1` FOREIGN KEY (`perennial_con_id`) REFERENCES `perennial_con_info` (`id`) ON DELETE CASCADE;
ALTER TABLE `participant_has_volunteer_shift` ADD CONSTRAINT `fk_participant_has_shift_to_shift` FOREIGN KEY (`volunteer_shift_id`) REFERENCES `volunteer_shift` (`id`) ON DELETE CASCADE, ADD CONSTRAINT `fk_participant_to_participant_has_shift` FOREIGN KEY (`badgeid`) REFERENCES `Participants` (`badgeid`) ON DELETE CASCADE;
ALTER TABLE `participant_on_session_history` ADD CONSTRAINT `participant_on_session_history_ibfk_1` FOREIGN KEY (`badgeid`) REFERENCES `Participants` (`badgeid`), ADD CONSTRAINT `participant_on_session_history_ibfk_2` FOREIGN KEY (`sessionid`) REFERENCES `Sessions` (`sessionid`), ADD CONSTRAINT `participant_on_session_history_ibfk_3` FOREIGN KEY (`change_by_badgeid`) REFERENCES `Participants` (`badgeid`);
ALTER TABLE `room_report_group_has_room` ADD CONSTRAINT `FK__room_report_group_has_room__room_report_group` FOREIGN KEY (`room_report_group_id`) REFERENCES `room_report_group` (`id`), ADD CONSTRAINT `FK__room_report_group_has_room__rooms` FOREIGN KEY (`room_id`) REFERENCES `Rooms` (`roomid`);
ALTER TABLE `volunteer_shift` ADD CONSTRAINT `fk_volunteer_shift_con_id` FOREIGN KEY (`con_id`) REFERENCES `con_info` (`id`), ADD CONSTRAINT `fk_volunteer_shift_to_volunteer_job` FOREIGN KEY (`volunteer_job_id`) REFERENCES `volunteer_job` (`id`) ON DELETE CASCADE;

SET FOREIGN_KEY_CHECKS=1;

INSERT INTO PatchLog (patchname) VALUES ('100ZED_utf8mb4_migration.sql');
