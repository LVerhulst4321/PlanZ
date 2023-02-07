import store from './store';
import axios from 'axios';
import { setModules } from './moduleActions';
import { redirectToLogin } from '../common/redirectToLogin';

export function fetchModules() {
    axios.get('/api/admin/modules.php')
        .then(res => {
            store.dispatch(setModules(res.data.modules));
        })
        .catch(error => {
            if (error.response && error.response.status === 401) {
                redirectToLogin();
            } else {
                let message = {
                    severity: "danger",
                    text: "The list of modules could not be downloaded."
                };
                store.dispatch(setModules({ list: [] }, message));
            }
        }
    );
}