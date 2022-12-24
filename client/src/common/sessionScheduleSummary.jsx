import React from "react";

export const SessionScheduleSummary = ({session}) => {

    if (session?.schedule) {
        return (<div><b>{session?.schedule?.room?.name} {' '}
            {session?.schedule?.room?.isOnline ? '(Online)' : ''}
            {' '} &#8226; {' '}
            {session?.track?.name}
            {' '} &#8226; {' '}
            {session?.schedule?.startTime}&#8211;{session?.schedule?.endTime}</b>
        </div>);
    } else {
        return null;
    }
}