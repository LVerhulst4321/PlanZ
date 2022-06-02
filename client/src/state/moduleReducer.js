import { SET_MODULES } from "./moduleActions";

const moduleInitialState = {
    loading: true,
    list: []
}

const moduleReducer = (state = moduleInitialState, action) => {
    switch (action.type) {
        case SET_MODULES: 
            return {
                ...state,
                message: null,
                list: action.payload.list || [],
                loading: false
            }
        default:
            return state;
    }
}

export default moduleReducer;