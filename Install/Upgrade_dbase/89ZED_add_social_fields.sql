## This script adds new tables for the participants various social media links.
##
## Created by Leane Verhulst
##

CREATE TABLE `link_types` (
  `linktypeid` int(11) NOT NULL AUTO_INCREMENT,
  `linktypename` varchar(50) DEFAULT NULL,
  `display_order` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`linktypeid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `link_types` (`linktypeid`, `linktypename`, `display_order`) VALUES
(1, 'Amazon', 10),
(2, 'Facebook', 20),
(3, 'GoodReads', 30),
(4, 'Instagram', 40),
(5, 'LinkedIn', 50),
(6, 'TikTok', 60),
(7, 'Twitch', 70),
(8, 'Twitter', 80),
(9, 'Website', 90),
(10, 'YouTube', 100),
(11, 'Other', 110);



CREATE TABLE `ParticipantLinks` (
  `badgeid` varchar(15) NOT NULL,
  `participantlinkid` int(11) NOT NULL,
  `linktypeid` int(11) NOT NULL,
  `linkvalue` varchar(100) DEFAULT NULL,
  `linkdescription` varchar(255) DEFAULT NULL,
  `lastupdate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `updatedby` varchar(15) NOT NULL,
  PRIMARY KEY (`badgeid`,`participantlinkid`),
  KEY `linktypeid` (`linktypeid`),
  CONSTRAINT `ParticipantLinks_ibfk_1` FOREIGN KEY (`badgeid`) REFERENCES `Participants` (`badgeid`),
  CONSTRAINT `ParticipantLinks_ibfk_2` FOREIGN KEY (`linktypeid`) REFERENCES `link_types` (`linktypeid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;





## Set up permissions.
INSERT INTO `PermissionAtoms` (`permatomid`, `permatomtag`, `page`, `notes`)
VALUES ('2065', 'ce_link_types', 'Edit Configuration Tables', 'enables edit');

INSERT INTO Permissions(permatomid, phaseid, permroleid, badgeid)
SELECT a.permatomid, null, r.permroleid, null 
FROM PermissionAtoms a
JOIN PermissionRoles r ON (r.permrolename = 'Administrator')
WHERE permatomtag IN  ('ce_link_types');



INSERT INTO PatchLog (patchname) VALUES ('89ZED_add_social_fields.sql');