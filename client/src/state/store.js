import { createStore, combineReducers } from 'redux'
import { SET_SHIFT_ASSIGNMENTS, SET_VOLUNTEER_JOBS, SET_VOLUNTEER_SHIFTS, SHOW_CREATE_JOB_MODAL, SHOW_CREATE_SHIFT_MODAL } from './volunteerActions';
import moduleReducer from './moduleReducer';

const volunteerInitialState = {
    assignments: {
        showModal: false,
        loading: true,
        list: []
    },
    shifts: {
        showModal: false,
        selectedShift: null,
        loading: true,
        list: [],
        context: null
    },
    jobs: {
        showModal: false,
        selectedJob: null,
        loading: true,
        list: []
    }
}

const volunteering = (state = volunteerInitialState, action) => {
    switch (action.type) {
        case SET_VOLUNTEER_JOBS: 
            return {
                ...state,
                message: action.payload.message,
                jobs: {
                    ...state.jobs,
                    list: action.payload.jobs || [],
                    loading: false
                }
            }
        case SET_VOLUNTEER_SHIFTS: 
            return {
                ...state,
                message: action.payload.message,
                shifts: {
                    ...state.shifts,
                    list: action.payload.shifts || [],
                    context: action.payload.context,
                    loading: false
                }
            }
        case SET_SHIFT_ASSIGNMENTS: 
            return {
                ...state,
                message: action.payload.message,
                assignments: {
                    ...state.assignments,
                    list: action.payload.shifts || [],
                    context: action.payload.context,
                    loading: false
                }
            }
        case SHOW_CREATE_JOB_MODAL: 
            return {
                ...state,
                jobs: {
                    ...state.jobs,
                    showModal: action.payload.show,
                    selectedJob: action.payload.selectedJob
                }
            }
        case SHOW_CREATE_SHIFT_MODAL: 
            return {
                ...state,
                shifts: {
                    ...state.shifts,
                    showModal: action.payload.show,
                    selectedShift: action.payload.selectedShift
                }
            }
        default:
            return state;
    }
};

const reducer = combineReducers({
    modules: moduleReducer,
    volunteering: volunteering
})
const store = createStore(reducer);

export default store;