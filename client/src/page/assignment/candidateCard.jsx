import React, { useState } from "react";
import Button from "react-bootstrap/Button";
import Card from "react-bootstrap/Card";
import { connect } from "react-redux";
import { createAssignment } from "../../state/assignmentsFunctions";

const CandidateCard = ({candidate, session, closeModal}) => {
    let [showBio, setShowBio] = useState(false);

    return (<Card className="mb-3">
        <Card.Body className="p-3">

            <div className="d-flex" style={{ gap: "0.5rem" }}>
                <div style={{ flexGrow: "0" }}>
                    <img src={candidate?.links?.avatar} className="rounded-circle participant-avatar participant-avatar-xs img-thumbnail" alt={'Avatar for ' + candidate.name} />
                </div>
                <div className="d-flex flex-column justify-content-between" style={{ flexGrow: "1" }}>
                    <div className="row">
                        <div className="col-md-6">
                            <div>
                                <b>{candidate?.name}</b>
                                <span className="text-muted small">({candidate?.badgeId})</span> {' '}
                                <Button variant="link" className="btn-sm" onClick={() => setShowBio(!showBio)}>[Bio]</Button>
                            </div>
                            {showBio
                                ? (<div className="text-muted small py-2">{candidate.textBio || "This person has no bio."}</div>)
                                : null }
                        </div>
                        <div className="col-md-6">
                            {candidate.interestResponse ? (<div><i>Rank: </i> {candidate.interestResponse?.rank}</div>) : undefined}
                            {candidate.interestResponse ? (<div className="small">{candidate.interestResponse?.comments}</div>) : undefined}
                        </div>
                    </div>
                </div>
                <div style={{ flexGrow: "0" }}>
                    <Button variant="outline-primary" size="sm"
                        onClick={() => { createAssignment(session?.sessionId, candidate?.badgeId); closeModal(); } }>Add</Button>
                </div>
            </div>
        </Card.Body>
    </Card>);
}

function mapStateToProps(state) {
    return {
        session: state.assignments.data.session ? state.assignments.data.session : undefined
    };
}

export default connect(mapStateToProps)(CandidateCard);