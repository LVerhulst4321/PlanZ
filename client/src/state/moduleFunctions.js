import store from './store';
import axios from 'axios';
import { setModules } from './moduleActions';

export function fetchModules() {
    axios.get('/api/admin/modules.php')
        .then(res => {
            store.dispatch(setModules(res.data.modules));
        })
        .catch(error => {
            let message = "The list of modeules could not be downloaded."
            store.dispatch(setModules({}, message));
        }
    );
}