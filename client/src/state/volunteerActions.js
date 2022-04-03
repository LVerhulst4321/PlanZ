import store from './store';
import axios from 'axios';

export const SET_VOLUNTEER_JOBS = 'SET_VOLUNTEER_JOBS';

export function fetchJobs() {
    axios.get(sdlc.serverUrl('/api/get_volunteer_jobs.php'))
        .then(res => {
            store.dispatch(setVolunteerJobs(res.data));
        })
        .catch(error => {
            let message = "The list of offerings could not be downloaded."
            store.dispatch(setOfferings({}, message));
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