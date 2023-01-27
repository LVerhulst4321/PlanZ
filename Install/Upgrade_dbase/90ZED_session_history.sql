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

INSERT INTO PatchLog (patchname) VALUES ('90ZED_session_history.sql');