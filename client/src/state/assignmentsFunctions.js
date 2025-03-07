import store from './store';
import axios from 'axios';
import { setOtherCandidateAssignments, setSessionAssignments } from './assignmentsActions';
import { redirectToLogin } from '../common/redirectToLogin';

export function fetchSessionAssignments(sessionId) {
    axios.get('/api/assignment/assignments.php?sessionId=' + encodeURIComponent(sessionId))
        .then(res => {
            store.dispatch(setSessionAssignments({
                id: sessionId,
                session: res.data.session,
                assignments: res.data.assignments,
                candidates: res.data.candidates
            }));
        })
        .catch(error => {
            if (error.response && error.response.status === 401) {
                redirectToLogin();
            } else {
                let message = {
                    severity: "danger",
                    text: "We've hit a bit of a technical snag trying to get information about that session."
                };
                store.dispatch(setSessionAssignments({ message: message }, message));
            }
        }
    );
}

export function fetchOtherAssignmentCandidates(sessionId, queryTerm) {
    axios.get('/api/assignment/find_potential_candidates.php?sessionId=' + encodeURIComponent(sessionId) + '&q=' + encodeURIComponent(queryTerm))
        .then(res => {
            store.dispatch(setOtherCandidateAssignments({
                candidates: res.data.candidates
            }));
        })
        .catch(error => {
            if (error.response && error.response.status === 401) {
                redirectToLogin();
            } else {
                let message = {
                    severity: "danger",
                    text: "We've hit a bit of a technical snag trying to get information about that session."
                };
                store.dispatch(setSessionAssignments({ message: message }, message));
            }
        }
    );
}
export function updateModeratorStatus(sessionId, badgeId, moderator) {
    axios.post('/api/assignment/assign_moderator.php', {
            sessionId: sessionId,
            badgeId: badgeId,
            moderator: moderator
        }).then(res => {
            fetchSessionAssignments(sessionId);
        })
        .catch(error => {
            if (error.response && error.response.status === 401) {
                redirectToLogin();
            } else {
                let message = {
                    severity: "danger",
                    text: "We've hit a bit of a technical snag trying to get information about that session."
                };
                store.dispatch(setSessionAssignments({ message: message }, message));
            }
        }
    );
}

export function removeAssignment(sessionId, badgeId) {
    axios.post('/api/assignment/remove_assignment.php', {
            sessionId: sessionId,
            badgeId: badgeId
        }).then(res => {
            fetchSessionAssignments(sessionId);
        })
        .catch(error => {
            if (error.response && error.response.status === 401) {
                redirectToLogin();
            } else {
                let message = {
                    severity: "danger",
                    text: "We've hit a bit of a technical snag trying to get information about that session."
                };
                store.dispatch(setSessionAssignments({ message: message }, message));
            }
        }
    );
}

export function updateNotes(sessionId, notes) {
    axios.post('/api/assignment/assign_note.php', {
            sessionId: sessionId,
            notes: notes
        }).then(res => {
            fetchSessionAssignments(sessionId);
        })
        .catch(error => {
            if (error.response && error.response.status === 401) {
                redirectToLogin();
            } else {
                let message = {
                    severity: "danger",
                    text: "We've hit a bit of a technical snag trying to update them thar notes."
                };
                store.dispatch(setSessionAssignments({ message: message }, message));
            }
        }
    );
}

export function createAssignment(sessionId, badgeId) {
    axios.post('/api/assignment/create_assignment.php', {
            sessionId: sessionId,
            badgeId: badgeId
        }).then(res => {
            fetchSessionAssignments(sessionId);
        })
        .catch(error => {
            if (error.response && error.response.status === 401) {
                redirectToLogin();
            } else {
                let message = {
                    severity: "danger",
                    text: "We've hit a bit of a technical snag trying to get information about that session."
                };
                store.dispatch(setSessionAssignments({ message: message }, message));
            }
        }
    );
}
