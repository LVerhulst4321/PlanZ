import React, { Component } from 'react';

import Alert from 'react-bootstrap/Alert';

class SimpleAlert extends Component {

    render() {
        if (this.props.message) {
            return (<Alert variant={this.props.message.severity}>{this.props.message.text}</Alert>);
        } else {
            return null;
        }
    }
}

export default SimpleAlert;