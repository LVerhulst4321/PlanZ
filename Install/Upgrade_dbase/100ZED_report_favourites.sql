## This script adds per-user favourite reports
##
##	Created by Andrew January on 2026-09-29
##
CREATE TABLE `ReportFavourites` (
    `badgeid` varchar(15) NOT NULL,
    `reportname` varchar(255) NOT NULL,
    PRIMARY KEY (`badgeid`, `reportname`),
    CONSTRAINT `ReportFavourites_badgeid_fk` FOREIGN KEY (`badgeid`) REFERENCES `Participants` (`badgeid`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

INSERT INTO PatchLog (patchname) VALUES ('100ZED_report_favourites.sql');
