import React from 'react';
import {decode} from 'html-entities';

const NameDisplay = ({ name }) => {
    if (name == null) {
        return null;
    } else if ((typeof name === 'string' || name instanceof String)) {
        return (<span>{decode(name)}</span>);
    } else if (name.badgeName && name.badgeName === name.pubsName) {
        return (<span>{decode(name.pubsName)}</span>);
    } else if (name.badgeName && name.pubsName) {
        return (<span>{decode(name.badgeName) + " (" + decode(name.pubsName) + ")"} </span>);
    } else if (name.firstName || name.lastName) {
        return (<span>{decode(name.firstName + " " + name.lastName)}</span>)
    } else {
        return null;
    }
}

export default NameDisplay;