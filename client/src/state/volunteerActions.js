export const SET_VOLUNTEER_JOBS = 'SET_VOLUNTEER_JOBS';
export const SHOW_CREATE_JOB_MODAL = 'SHOW_CREATE_JOB_MODAL';
export const SET_VOLUNTEER_SHIFTS = 'SET_VOLUNTEER_SHIFTS';
export const SHOW_CREATE_SHIFT_MODAL = 'SHOW_CREATE_SHIFT_MODAL';
export const SET_SHIFT_ASSIGNMENTS = 'SET_SHIFT_ASSIGNMENTS';

export function setVolunteerJobs(jobs, message = null) {
    let payload = {
        ...jobs,
        message: message
    }
    return {
        type: SET_VOLUNTEER_JOBS,
        payload
    }
}

export function setVolunteerShifts(shifts, message = null) {
    let payload = {
        ...shifts,
        message: message
    }
    return {
        type: SET_VOLUNTEER_SHIFTS,
        payload
    }
}

export function setShiftAssignements(assignments, message = null) {
    let payload = {
        ...assignments,
        message: message
    }
    return {
        type: SET_SHIFT_ASSIGNMENTS,
        payload
    }
}

export function showCreateJobModal(show = true) {
    let payload = {
        show: show
    }
    return {
        type: SHOW_CREATE_JOB_MODAL,
        payload
    }
}

export function showCreateShiftModal(show = true) {
    let payload = {
        show: show
    }
    return {
        type: SHOW_CREATE_SHIFT_MODAL,
        payload
    }
}