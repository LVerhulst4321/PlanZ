import React, { Component } from 'react';
import { render } from 'react-dom';

class App extends Component {
    constructor(...args) {
        super(...args);

        this.state = {
            name: 'PlanZ'
        };
    }

    render() {
        return <div>This is {this.state.name}!</div>;
    }
}

render(<App />, document.getElementById('app'));