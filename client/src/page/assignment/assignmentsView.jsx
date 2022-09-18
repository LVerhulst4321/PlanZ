import React from "react";
import { useEffect } from "react";
import { Spinner } from "react-bootstrap";
import { connect } from "react-redux";
import SimpleAlert from "../../common/simpleAlert";
import { fetchSessionAssignments } from "../../state/assignmentsFunctions";

const AssignmentsView = (props) => {

    useEffect(() => fetchSessionAssignments(props.sessionId), []);

    if (props.loading) {
        return (
            <div className="text-center">
                <Spinner animation="border" />
            </div>
        );
    } else {

        let sessionBlock = (props.session) ? (<div>
                <h3>{props.session.title}</h3>
                <div>{props.session.programGuideDescription}</div>
                <div><i>Notes:</i> {props.session.notesForProgramStaff}</div>
            </div>) : undefined;
        return (<div>
            <SimpleAlert message={props.message} />
            {sessionBlock}
            </div>)
    }
}

function mapStateToProps(state) {
    return { 
        session: state.assignments.data.session ? state.assignments.data.session : undefined,
        assignments: state.assignments.data.assignments ? state.assignments.assignments : undefined,
        loading: state.assignments.data.loading,
        message: state.assignments.data.message
    };
}

export default connect(mapStateToProps)(AssignmentsView);