import store from './store';
import axios from 'axios';
import { setModules } from './moduleActions';

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
                    text: "The list of modeules could not be downloaded."
                };
                store.dispatch(setModules({ list: [] }, message));
            }
        }
    );
}