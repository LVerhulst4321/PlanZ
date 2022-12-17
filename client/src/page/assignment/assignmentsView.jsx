import React from "react";
import { useEffect } from "react";
import { Alert, Button } from "react-bootstrap";
import Spinner from "react-bootstrap/Spinner";
import { connect } from "react-redux";
import SimpleAlert from "../../common/simpleAlert";
import { fetchSessionAssignments } from "../../state/assignmentsFunctions";
import { AssignmentCard } from "./assignmentCard";

const AssignmentsView = (props) => {

    const isModeratorPresent = () => {
        return props.assignments.filter(a => a.moderator).length;
    }

    useEffect(() => fetchSessionAssignments(props.sessionId), []);

    if (props.loading) {
        return (
            <div className="text-center">
                <Spinner animation="border" />
            </div>
        );
    } else {

        let sessionBlock = (props.session) ? (<div className="mb-3">
                <h3>{props.session.title}</h3>
                <div>{props.session.programGuideDescription}</div>
                <div><i>Notes:</i> {props.session.notesForProgramStaff || "None"}</div>
            </div>) : undefined;

        let assignmentBlock = (props.assignments)
            ? (<div>
                    <div className="d-flex justify-content-between">
                        <h4>Currently Assigned</h4>
                        <Button variant="outline-secondary">Add</Button>
                    </div>
                    {isModeratorPresent() ? null : (<Alert variant="warning" className="mt-3">This session has no moderator.</Alert>)}
                    <div>
                        {props.assignments.map(a => { return (<div className="my-3" key={a.badgeId}><AssignmentCard assignee={a} /></div>); })}
                    </div>
                </div>)
            : undefined;
        return (<div>
            <SimpleAlert message={props.message} />
            {sessionBlock}
            {assignmentBlock}
            </div>)
    }
}

function mapStateToProps(state) {
    return {
        session: state.assignments.data.session ? state.assignments.data.session : undefined,
        assignments: state.assignments.data.assignments ? state.assignments.data.assignments : undefined,
        loading: state.assignments.data.loading,
        message: state.assignments.data.message
    };
}

export default connect(mapStateToProps)(AssignmentsView);