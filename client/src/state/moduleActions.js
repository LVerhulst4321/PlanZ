export const SET_MODULES = 'SET_MODULES';

export function setModules(modules, message = null) {
    let payload = {
        list: modules,
        message: message
    }
    return {
        type: SET_MODULES,
        payload
    }
}
