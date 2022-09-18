import { SET_SESSION_ASSIGNMENTS } from "./assignmentsActions";

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
                    ...action.payload,
                    loading: false
                }
            }
        default:
            return state;
    }
}

export default assignmentsReducer;