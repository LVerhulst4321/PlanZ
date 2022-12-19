import store from './store';
import axios from 'axios';
import { setSessionAssignments } from './assignmentsActions';
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