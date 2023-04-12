import React from 'react';

const NameDisplay = ({ name }) => {
    if (name.badgeName && name.badgeName === name.pubsName) {
        return (<span>{name.pubsName}</span>);
    } else if (name.badgeName && name.pubsName) {
        return (<span>{name.badgeName + " (" + name.pubsName + ")"} </span>);
    } else if (name.firstName || name.lastName) {
        return (<span>{name.firstName + " " + name.lastName}</span>)
    } else {
        return null;
    }
}

export default NameDisplay;