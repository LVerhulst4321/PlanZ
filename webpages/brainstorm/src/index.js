import React from 'react';
import ReactDOM from 'react-dom';
import { Provider } from 'react-redux';
import BrainstormApp from './BrainstormApp';
import store from './state/store';

// Importing the Bootstrap CSS
//import 'bootstrap/dist/css/bootstrap.min.css';

ReactDOM.render(<Provider store={store}><BrainstormApp /></Provider>, document.getElementById('root'));
