import React, { useEffect, useState } from "react";
import Alert from "react-bootstrap/Alert";
import Button from "react-bootstrap/Button";
import Modal from "react-bootstrap/Modal";
import Tabs from "react-bootstrap/Tabs";
import Tab from "react-bootstrap/Tabs";
import Spinner from "react-bootstrap/Spinner";
import { connect } from "react-redux";
import { SessionScheduleSummary } from "../../common/sessionScheduleSummary";
import SimpleAlert from "../../common/simpleAlert";
import { fetchSessionAssignments } from "../../state/assignmentsFunctions";
import AssignmentCard from "./assignmentCard";
import CandidateCard from "./candidateCard";

const AssignmentsView = (props) => {

    const [showModal, setShowModal] = useState(false);

    const isModeratorPresent = () => {
        return props.assignments.filter(a => a.moderator).length;
    }

    const renderModal = ({candidates, session}) => {
        return (<Modal show={showModal} size="lg" onHide={() => setShowModal(false)}>
            <Modal.Header closeButton>
                <Modal.Title>Assign {props.session?.participantLabel ? props.session?.participantLabel : 'Participants'}</Modal.Title>
            </Modal.Header>
            <Modal.Body>
                <Tabs defaultActiveKey="interested" className="mb-3">
                    <Tab eventKey="interested" title="Interested">
                        <p>These people expressed interest in participating in this session.</p>
                        {candidates?.map(c => (<CandidateCard candidate={c} session={session}
                            closeModal={() => setShowModal(false)} key={'candidate-' + c.badgeId} />))}
                    </Tab>
                    <Tab eventKey="other" title="Other">
                        <p>Someone else</p>
                    </Tab>
                </Tabs>
            </Modal.Body>
        </Modal>);
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
                <SessionScheduleSummary session={props.session} />
                <div>{props.session.programGuideDescription}</div>
                <div><i>Notes:</i> {props.session.notesForProgramStaff || "None"}</div>
            </div>) : undefined;

        let assignmentBlock = (props.assignments)
            ? (<div>
                    <div className="d-flex justify-content-between">
                        <h4>Currently Assigned</h4>
                        <Button variant="outline-secondary" onClick={() => setShowModal(true)}>Add</Button>
                    </div>
                    {isModeratorPresent() ? null : (<Alert variant="warning" className="mt-3">This session has no moderator.</Alert>)}
                    <div>
                        {props.assignments.map(a => { return (<div className="my-3" key={a.badgeId}><AssignmentCard assignee={a} /></div>); })}
                    </div>
                </div>)
            : undefined;
        return (<>
                <div>
                <SimpleAlert message={props.message} />
                {sessionBlock}
                {assignmentBlock}
                </div>
                {renderModal(props)}
            </>)
    }
}

function mapStateToProps(state) {
    return {
        session: state.assignments.data?.session ? state.assignments.data.session : undefined,
        assignments: state.assignments.data?.assignments ? state.assignments.data.assignments : undefined,
        candidates: state.assignments.data?.candidates,
        loading: state.assignments.data.loading,
        message: state.assignments.data.message
    };
}

export default connect(mapStateToProps)(AssignmentsView);