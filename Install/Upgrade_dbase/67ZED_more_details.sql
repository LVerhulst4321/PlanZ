## This script adds new tables for more Participant personal details
##
##  Created by Leane Verhulst on August 21, 2021
##

CREATE TABLE `AgeRanges` (
  `agerangeid` int(11) NOT NULL AUTO_INCREMENT,
  `agerangename` varchar(100) DEFAULT NULL,
  `display_order` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`agerangeid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `AgeRanges` (`agerangeid`, `agerangename`, `display_order`) VALUES
(1, 'No Answer', 1),
(2, 'Under 18 Years', 2),
(3, '18 to 24 Years', 3),
(4, '25 Years and Over', 4);

CREATE TABLE `Pronouns` (
  `pronounid` int(11) NOT NULL AUTO_INCREMENT,
  `pronounname` varchar(100) DEFAULT NULL,
  `display_order` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`pronounid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `Pronouns` (`pronounid`, `pronounname`, `display_order`) VALUES
(1, 'No answer', 1),
(2, 'He/Him', 2),
(3, 'She/Her', 3),
(4, 'They/Them', 4),
(99, 'Other, please specify', 99);



CREATE TABLE `ParticipantDetails` (
  `badgeid` varchar(15) NOT NULL,
  `dayjob` varchar(255) DEFAULT NULL,
  `accessibilityissues` text DEFAULT NULL,
  `ethnicity` varchar(255) DEFAULT NULL,
  `gender` varchar(255) DEFAULT NULL,
  `sexualorientation` varchar(255) DEFAULT NULL,
  `agerangeid` int(11) NOT NULL DEFAULT 1,
  `pronounid` int(11) NOT NULL DEFAULT 1,
  `pronounother` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`badgeid`),
  KEY `agerangeid` (`agerangeid`),
  KEY `pronounid` (`pronounid`),
  CONSTRAINT `ParticipantDetails_ibfk_1` FOREIGN KEY (`badgeid`) REFERENCES `Participants` (`badgeid`),
  CONSTRAINT `ParticipantDetails_ibfk_2` FOREIGN KEY (`agerangeid`) REFERENCES `AgeRanges` (`agerangeid`),
  CONSTRAINT `ParticipantDetails_ibfk_3` FOREIGN KEY (`pronounid`) REFERENCES `Pronouns` (`pronounid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;





## Set up permissions.
INSERT INTO `PermissionAtoms` (`permatomid`, `permatomtag`, `page`, `notes`)
VALUES ('2051', 'ce_AgeRanges', 'Edit Configuration Tables', 'enables edit');

INSERT INTO `PermissionAtoms` (`permatomid`, `permatomtag`, `page`, `notes`)
VALUES ('2052', 'ce_Pronouns', 'Edit Configuration Tables', 'enables edit');

INSERT INTO Permissions(permatomid, phaseid, permroleid, badgeid)
SELECT a.permatomid, null, r.permroleid, null 
FROM PermissionAtoms a
JOIN PermissionRoles r ON (r.permrolename = 'Administrator')
WHERE permatomtag IN  ('ce_AgeRanges', 'ce_Pronouns');



INSERT INTO PatchLog (patchname) VALUES ('67ZED_more_details.sql');
