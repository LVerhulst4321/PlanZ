import { createStore, combineReducers } from 'redux'

const volunteerInitialState = {
    loading: true,
    jobs: []
}

const volunteering = (state = volunteerInitialState, action) => {
    switch (action.type) {
        case SET_VOLUNTEER_JOBS: 
            return {
                ...state,
                loading: false,
                message: action.payload.message,
                jobs: action.payload.jobs || []
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