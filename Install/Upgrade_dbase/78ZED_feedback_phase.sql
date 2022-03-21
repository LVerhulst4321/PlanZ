## Adds permission to Administrator role.
##
## Created by Leane Verhulst
##

INSERT INTO `Permissions` (permatomid, phaseid, permroleid)
SELECT a.permatomid, p.phaseid, r.permroleid
FROM PermissionAtoms a,
     Phases p,
     PermissionRoles r
WHERE a.permatomtag = 'SessionFeedback'
  AND p.phasename = 'Feedback and Interest'
  AND r.permrolename = 'Administrator';

INSERT INTO PatchLog (patchname) VALUES ('78ZED_feedback_phase.sql');
