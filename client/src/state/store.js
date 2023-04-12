import { createStore, combineReducers } from 'redux'
import { REMEMBER_RECENT_SHIFT_DATA, SET_ALL_SHIFT_ASSIGNMENTS, SET_SHIFT_ASSIGNMENTS, SET_VOLUNTEER_JOBS, SET_VOLUNTEER_SHIFTS, SHOW_CREATE_JOB_MODAL, SHOW_CREATE_SHIFT_MODAL } from './volunteerActions';
import brainstormReducer from './brainstormReducer';
import moduleReducer from './moduleReducer';
import assignmentsReducer from './assignmentsReducer';

const volunteerInitialState = {
    assignments: {
        showModal: false,
        loading: true,
        list: []
    },
    allAssignments: {
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
    },
    recentData: {
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
        case SET_ALL_SHIFT_ASSIGNMENTS:
            return {
                ...state,
                message: action.payload.message,
                allAssignments: {
                    ...state.assignments,
                    list: action.payload.shifts || [],
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
        case REMEMBER_RECENT_SHIFT_DATA:
            return {
                ...state,
                recentData: action.payload.values
            }
        default:
            return state;
    }
};

const reducer = combineReducers({
    modules: moduleReducer,
    volunteering: volunteering,
    assignments: assignmentsReducer,
    brainstorm: brainstormReducer
})
const store = createStore(reducer);

export default store;