export const SET_SESSION_ASSIGNMENTS = 'SET_SESSION_ASSIGNMENTS';
export const SET_OTHER_CANDIDATE_ASSIGNMENTS = 'SET_OTHER_CANDIDATE_ASSIGNMENTS';

export const setSessionAssignments = (assignmentData) => {
    let payload = assignmentData;
    return {
        type: SET_SESSION_ASSIGNMENTS,
        payload
    }
};

export const setOtherCandidateAssignments = (data) => {
    let payload = data.candidates;
    return {
        type: SET_OTHER_CANDIDATE_ASSIGNMENTS,
        payload
    }
};