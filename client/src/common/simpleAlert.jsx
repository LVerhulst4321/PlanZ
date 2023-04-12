import React from 'react';
import Alert from 'react-bootstrap/Alert';

const SimpleAlert = ({message}) => {

    if (message) {
        if ((typeof message === 'string' || message instanceof String)) {
            return (<Alert variant="danger">{message}</Alert>);
        } else {
            return (<Alert variant={message.severity}>{message.text}</Alert>);
        }
    } else {
        return null;
    }
}

export default SimpleAlert;