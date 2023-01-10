##
## Make volunteer shifts con-specific
##
## Created by BC Holmes
##

alter table volunteer_shift add column con_id int(11);

update volunteer_shift set con_id = (select max(id) from current_con) where con_id is null;

alter table volunteer_shift modify con_id int(11) not null;
alter table volunteer_shift add CONSTRAINT `fk_volunteer_shift_con_id` FOREIGN KEY (`con_id`) REFERENCES `con_info` (`id`);

INSERT INTO PatchLog (patchname) VALUES ('89ZED_volunteer_to_con_info.sql');