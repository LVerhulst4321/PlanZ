## This script adds a new phase to the system. This new phase allows people to provide feedback on proposed sessions.
##
## Created by BC Holmes
##

INSERT INTO `Phases` (phasename, current, notes, implemented, display_order)
VALUES ('Feedback and Interest', 0, 'Add on Session Feedback', 1, 1000);

INSERT INTO `PermissionAtoms` (permatomid, permatomtag, page, notes) 
VALUES (31, 'SessionFeedback', 'SessionFeedback', 'user can provide session feedback');

INSERT INTO `Permissions` (permatomid, phaseid, permroleid)
SELECT a.permatomid, p.phaseid, r.permroleid
FROM PermissionAtoms a,
     Phases p,
     PermissionRoles r
WHERE a.permatomtag = 'SessionFeedback'
  AND p.phasename = 'Feedback and Interest'
  AND r.permrolename = 'Program Participant';

INSERT INTO PatchLog (patchname) VALUES ('62WIS_feedback_phase.sql');
