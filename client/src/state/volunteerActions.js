import store, { SET_VOLUNTEER_JOBS, SHOW_CREATE_JOB_MODAL } from './store';
import axios from 'axios';

export function fetchJobs() {
    axios.get('/api/volunteer/get_volunteer_jobs.php')
        .then(res => {
            store.dispatch(setVolunteerJobs(res.data));
        })
        .catch(error => {
            let message = "The list of offerings could not be downloaded."
            store.dispatch(setVolunteerJobs({}, message));
        }
    );
}

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

export function showCreateJobModal(show = true) {
    let payload = {
        show: show
    }
    return {
        type: SHOW_CREATE_JOB_MODAL,
        payload
    }
}