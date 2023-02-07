import React from "react";
import AssignmentsView from "./assignmentsView";

const AssignmentsPage = () => {

    let paramString = window.location.href.split('?')[1];
    let params = new URLSearchParams(paramString);

    let sessionId = params.get('sessionId');

    return (<div className="card mb-3">
        <div className="card-header">
            <h2>Assign Participants</h2>
        </div>
        <div className="card-body">
            <AssignmentsView sessionId={sessionId} />
        </div>
    </div>);

}

export default AssignmentsPage;