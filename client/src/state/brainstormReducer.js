import { SAVE_BRAINSTORM_OPTIONS } from "./brainstormActions";

const brainstormReducer = (state = {}, action) => {
    switch (action.type) {
        case SAVE_BRAINSTORM_OPTIONS: 
            return { ...state,
                con: action.payload.con,
                divisions: action.payload.divisions,
                customText: action.payload.customText 
            };
        default:
            return state;
    }
};

export default brainstormReducer;