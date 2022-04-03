import { createStore, combineReducers } from 'redux'

export const SET_VOLUNTEER_JOBS = 'SET_VOLUNTEER_JOBS';
export const SHOW_CREATE_JOB_MODAL = 'SHOW_CREATE_JOB_MODAL';

const volunteerInitialState = {
    jobs: {
        showModal: false,
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
        case SHOW_CREATE_JOB_MODAL: 
            return {
                ...state,
                jobs: {
                    ...state.jobs,
                    showModal: action.payload.show
                }
            }
        default:
            return state;
    }
};

const reducer = combineReducers({
    volunteering
})
const store = createStore(reducer);

export default store;