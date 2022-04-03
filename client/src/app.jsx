import React, { Component } from 'react';
import { render } from 'react-dom';
import { Provider } from 'react-redux';
import CompositePage from './page/compositePage';
import store from './state/store';

class App extends Component {
    constructor(...args) {
        super(...args);

        this.state = {
            name: 'PlanZ'
        };
    }

    render() {
        return (<CompositePage />);
    }
}

render(<Provider store={store}><App /></Provider>, document.getElementById('app'));