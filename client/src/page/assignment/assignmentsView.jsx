import React, { useEffect, useState } from "react";
import Alert from "react-bootstrap/Alert";
import Button from "react-bootstrap/Button";
import Modal from "react-bootstrap/Modal";
import Spinner from "react-bootstrap/Spinner";
import { connect } from "react-redux";
import { SessionScheduleSummary } from "../../common/sessionScheduleSummary";
import SimpleAlert from "../../common/simpleAlert";
import { fetchOtherAssignmentCandidates, fetchSessionAssignments, updateNotes } from "../../state/assignmentsFunctions";
import AssignmentCard from "./assignmentCard";
import CandidateCard from "./candidateCard";

var timeout = null;

const AssignmentsView = (props) => {
    const [showModal, setShowModal] = useState(false);
    const [term, setTerm] = useState("");
    const [editNote, setEditNote] = useState(false);

    const isModeratorPresent = () => {
        return props.assignments.filter(a => a.moderator).length;
    }

    const executeQuery = (session, queryString) => {
        if (timeout) {
            clearTimeout(timeout);
            timeout = undefined;
        }
        setTerm(queryString);
        if (queryString) {
            timeout = setTimeout(() => {
                fetchOtherAssignmentCandidates(session.sessionId, queryString);
                timeout = undefined;
            }, 1000);
        }
    }

    const renderMessages = () => {
        if (!props.assignments?.length) {
            return (<Alert variant="warning" className="mt-3">This session has no participants.</Alert>);
        } else if (!isModeratorPresent()) {
            return (<Alert variant="warning" className="mt-3">This session has no moderator.</Alert>);
        } else {
            return null;
        }
    }

    const renderModal = ({session, otherCandidates}) => {
        return (<Modal show={showModal} size="lg" onHide={() => setShowModal(false)}>
            <Modal.Header closeButton>
                <Modal.Title>Assign {props.session?.participantLabel ? props.session?.participantLabel : 'Participants'}</Modal.Title>
            </Modal.Header>
            <Modal.Body>
                <p>Search for a potential candidate:</p>
                <div className="row">
                    <div className="form-group col-md-6">
                        <label htmlFor="candidate-search" className="sr-only">Search</label>
                        <input type="text" id="candidate-search" className="form-control"
                            value={term} autoComplete="off" name="q" placeholder="Search..."
                            onChange={(e) => executeQuery(session, e.target.value)} />
                    </div>
                </div>
                {otherCandidates?.map(c => (<CandidateCard candidate={c} session={session}
                    closeModal={() => setShowModal(false)} key={'other-' + c.badgeId} />))}
                {otherCandidates?.length ? null : (<p className="my-3 text-info">This list is empty.</p>)}
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

        let sessionBlock = (props.session)
            ? (
                <div className="mb-3 visible-on-hover">
                    <h3>{props.session.title}</h3>
                    <SessionScheduleSummary session={props.session} />
                    <div>{props.session.programGuideDescription}</div>
                    <div>
                        {editNote
                        ? (<div className="form-group py-3">
                            <input type="text" className="form-control" autoFocus placeholder="Notes..." defaultValue={props.session.notesForProgramStaff ?? ""} onBlur={(e) => {
                                updateNotes(props.session.sessionId, e.target.value);
                                setEditNote(false);
                            }} />
                        </div>)
                        : (<>
                            <span className="d-inline-block py-3"><i>Notes:</i> {props.session.notesForProgramStaff || "None"}</span>
                            <button className="btn" onClick={() => setEditNote(true)}><i className="bi bi-pencil"></i></button>
                        </>)}
                    </div>
                </div>)
            : undefined;

        const moderatorCandidates = props.candidates.filter(c => c.interestResponse && c.interestResponse.willModerate);
        const moderators = moderatorCandidates?.length
            ? (<>
                    <div className="d-flex justify-content-between mt-5">
                        <h4>Potential Moderators</h4>
                    </div>

                    <p>These people have expressed interest in moderating this session.</p>

                    <div>
                        {moderatorCandidates.map(c => { return (<div className="my-3" key={c.badgeId}><AssignmentCard assignee={c}  assigned={false}/></div>); })}
                    </div>
                </>)
            : (<></>);

        const rankedCandidates = props.candidates.filter(c => c.interestResponse && c.interestResponse.rank && !c.interestResponse.willModerate);
        const ranked = rankedCandidates?.length
            ? (<>
                    <div className="d-flex justify-content-between mt-5">
                        <h4>Potential Participants</h4>
                    </div>

                    <p>These people have expressed interest in the session.</p>

                    <div>
                        {rankedCandidates.map(c => { return (<div className="my-3" key={c.badgeId}><AssignmentCard assignee={c}  assigned={false}/></div>); })}
                    </div>
                </>)
            : (<></>);

        const unrankedCandidates = props.candidates.filter(c => !c.interestResponse || !c.interestResponse.rank && !c.interestResponse.willModerate);
        const unranked = unrankedCandidates?.length
            ? (<>
                    <div className="d-flex justify-content-between mt-5">
                        <h4>Unranked Candidates</h4>
                    </div>

                    <p>These people have expressed interest, but have not ranked this session.</p>

                    <div>
                        {unrankedCandidates.map(c => { return (<div className="my-3" key={c.badgeId}><AssignmentCard assignee={c}  assigned={false}/></div>); })}
                    </div>
                </>)
            : (<></>);

        const noCandidates = props.candidates?.length
            ? <></>
            : (
                <div>
                    <div className="d-flex justify-content-between mt-5">
                        <h4>No Candidates</h4>
                    </div>
                    <p className="text-info">There are currently no candidates for this session.</p>
                </div>
            );

        let assignmentBlock = (props.assignments)
            ? (<div>
                    <div className="d-flex justify-content-between">
                        <h4>Currently Assigned</h4>
                        <Button variant="outline-secondary" onClick={() => setShowModal(true)}>Add</Button>
                    </div>
                    {renderMessages()}
                    <div>
                        {props.assignments.map(a => { return (<div className="my-3" key={a.badgeId}><AssignmentCard assignee={a} assigned={true} /></div>); })}
                        {props.assignments?.length ? null : (<p className="text-info">This list is empty.</p>)}
                    </div>

                    {moderators}
                    {ranked}
                    {unranked}
                    {noCandidates}

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
        message: state.assignments.data.message,
        otherCandidates: state.assignments.data.otherCandidates
    };
}

export default connect(mapStateToProps)(AssignmentsView);
