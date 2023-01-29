##
## Make volunteer shifts con-specific
##
## Created by BC Holmes
##

ALTER TABLE `PreviousConTracks`
DROP FOREIGN KEY `PreviousCons_ibfk_1`;

ALTER TABLE `PreviousSessions`
DROP FOREIGN KEY `PreviousSessions_ibfk_1`;

DROP TABLE PreviousCons;

create view PreviousCons as
select id as previousconid, name as previousconname, display_order from con_info where active_to_time < CURRENT_TIMESTAMP;

alter table `PreviousConTracks`
add CONSTRAINT `PreviousCons_ibfk_1` FOREIGN KEY (`previousconid`) REFERENCES `con_info` (`id`);

alter table `PreviousSessions`
add CONSTRAINT `PreviousSessions_ibfk_1` FOREIGN KEY (`previousconid`) REFERENCES `con_info` (`id`);


INSERT INTO PatchLog (patchname) VALUES ('91ZED_previous_cons.sql');