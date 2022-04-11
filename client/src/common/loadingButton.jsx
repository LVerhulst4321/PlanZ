import React, { Component } from 'react';

import Button from 'react-bootstrap/Button';
import Spinner from 'react-bootstrap/Spinner';

class LoadingButton extends Component {

    render() {
        const spinner = this.props.loading ? (<Spinner
            as="span"
            animation="border"
            size="sm"
            role="status"
            aria-hidden="true"
        />) : undefined;
        return (<Button variant={this.props.variant ? this.props.variant : "primary"} 
            onClick={() => this.props.onClick()} 
            disabled={!this.props.enabled || this.props.loading}>{spinner} {this.props.children}</Button>);
    }
}

export default LoadingButton;