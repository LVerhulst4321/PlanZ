import React, { Component } from 'react';
import CompositePage from './page/compositePage';

export class App extends Component {
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
