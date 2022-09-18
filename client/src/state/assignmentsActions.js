export const SET_SESSION_ASSIGNMENTS = 'SET_SESSION_ASSIGNMENTS';

export const setSessionAssignments = (assignmentData) => {
    let payload = assignmentData;
    return {
        type: SET_SESSION_ASSIGNMENTS,
        payload
    }
};