##
## retool the session history.
##
## Created by BC Holmes
##

create table participant_on_session_history (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `badgeid` varchar(15) NOT NULL DEFAULT '',
  `sessionid` int(11) NOT NULL DEFAULT '0',
  `moderator` tinyint(4) NOT NULL DEFAULT '0',
  `change_ts` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `change_by_badgeid` varchar(15) NOT NULL DEFAULT '',
  `change_type` varchar(32) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  CONSTRAINT `participant_on_session_history_ibfk_1` FOREIGN KEY (`badgeid`) REFERENCES `Participants` (`badgeid`),
  CONSTRAINT `participant_on_session_history_ibfk_2` FOREIGN KEY (`sessionid`) REFERENCES `Sessions` (`sessionid`),
  CONSTRAINT `participant_on_session_history_ibfk_3` FOREIGN KEY (`change_by_badgeid`) REFERENCES `Participants` (`badgeid`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;

insert into participant_on_session_history
(`badgeid`, `sessionid`, `moderator`, `change_ts`, `change_by_badgeid`, `change_type`)
select `badgeid`, `sessionid`, `moderator`, `createdts`, `createdbybadgeid`, 'insert_assignment'
from ParticipantOnSessionHistory
where createdbybadgeid is not null;

insert into participant_on_session_history
(`badgeid`, `sessionid`, `moderator`, `change_ts`, `change_by_badgeid`, `change_type`)
select `badgeid`, `sessionid`, `moderator`, `inactivatedts`, `inactivatedbybadgeid`, 'insert_assignment'
from ParticipantOnSessionHistory
where inactivatedbybadgeid is not null;

drop table ParticipantOnSessionHistory;

create view session_change_history as
(SELECT
    SEH.sessionid,
    SEH.badgeid as change_by_badgeid,
    CONCAT(CD.firstname, " ", CD.lastname) AS change_by_name,
    CONCAT(SEC.description,
        (CASE WHEN SEH.editdescription IS NOT NULL THEN CONCAT(" — ", SEH.editdescription)
        ELSE ""
        END),
        " — status: ",
        SS.statusname) as description,
    SEH.timestamp as change_ts
FROM
         SessionEditHistory SEH
    JOIN SessionEditCodes SEC USING (sessioneditcode)
    JOIN SessionStatuses SS USING (statusid)
    JOIN CongoDump CD ON CD.badgeid = SEH.badgeid
)

UNION

(SELECT
    POSH.sessionid,
    POSH.change_by_badgeid,
    PartCR.pubsname AS change_by_name,
    (CASE WHEN POSH.change_type = 'insert_assignment' THEN CONCAT('Add ', PartOS.pubsname, ' to session')
            WHEN POSH.change_type = 'remove_assignment' THEN CONCAT('Remove ', PartOS.pubsname, ' from session')
            WHEN POSH.change_type = 'assign_moderator' THEN CONCAT('Assign ', PartOS.pubsname, ' as moderator')
            WHEN POSH.change_type = 'remove_moderator' THEN CONCAT('Unassign ', PartOS.pubsname, ' as moderator')
            ELSE 'Unknown action.' END) as description,
    POSH.change_ts
FROM
              participant_on_session_history POSH
         JOIN Participants PartOS ON PartOS.badgeid = POSH.badgeid
         JOIN Participants PartCR ON PartCR.badgeid = POSH.change_by_badgeid
);


INSERT INTO PatchLog (patchname) VALUES ('90ZED_session_history.sql');