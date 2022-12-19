import { SET_OTHER_CANDIDATE_ASSIGNMENTS, SET_SESSION_ASSIGNMENTS } from "./assignmentsActions";

const initialState = {
    data: {
        loading: true
    }
}

const assignmentsReducer = (state = initialState, action) => {
    switch (action.type) {
        case SET_SESSION_ASSIGNMENTS:
            return {
                ...state,
                data: {
                    ...state.data,
                    ...action.payload,
                    loading: false
                }
            }
        case SET_OTHER_CANDIDATE_ASSIGNMENTS:
            return {
                ...state,
                data: {
                    ...state.data,
                    otherCandidates: action.payload
                }
            }
        default:
            return state;
    }
}

export default assignmentsReducer;