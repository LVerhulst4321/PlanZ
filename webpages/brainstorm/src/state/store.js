import { createStore, combineReducers } from 'redux'
import { ADD_AUTH_CREDENTIAL, HIDE_LOGIN_MODAL, LOGOUT, LOGOUT_AND_SHOW_MODAL, SHOW_LOGIN_MODAL } from './authActions';
import { SAVE_OPTIONS } from './optionsActions';

const authInitialState = {
    showModal: false,
    pending: true,
    jwt: undefined
}

const auth = (state = authInitialState, action) => {
    switch (action.type) {
        case ADD_AUTH_CREDENTIAL: 
            return {
                ...state,
                pending: false,
                jwt: action.payload.jwt
            }
        case SHOW_LOGIN_MODAL: 
            return {
                ...state,
                showModal: true
            }
        case HIDE_LOGIN_MODAL: 
            return {
                ...state,
                showModal: false
            }
        case LOGOUT_AND_SHOW_MODAL: 
            return {
                ...state,
                pending: false,
                showModal: true,
                jwt: undefined
            };
        case LOGOUT: 
            return {
                ...state,
                pending: false,
                jwt: undefined
            };
        default:
            return state;
    }
};

const options = (state = {}, action) => {
    switch (action.type) {
        case SAVE_OPTIONS: 
            return { ...state,
                divisions: action.payload.divisions 
            };
        default:
            return state;
    }
};

const reducer = combineReducers({
    auth, options
})
const store = createStore(reducer);

export default store;