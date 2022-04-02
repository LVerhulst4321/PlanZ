import React, { Component } from 'react';
import { render } from 'react-dom';
import CompositePage from './page/compositePage';

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

render(<App />, document.getElementById('app'));