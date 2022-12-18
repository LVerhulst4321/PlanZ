import React, { useState } from "react";
import Button from "react-bootstrap/Button";
import Card from "react-bootstrap/Card";
import { connect } from "react-redux";
import { updateModeratorStatus } from "../../state/assignmentsFunctions";

const AssignmentCard = ({assignee, session}) => {
    let [showBio, setShowBio] = useState(false);

    return (<Card className={assignee.moderator ? 'border-success' : ''}>
        <Card.Body className="p-3">

            <div className="d-flex" style={{ gap: "1rem" }}>
                <div className="mr-3" style={{ flexGrow: "0" }}>
                    <img src={assignee.links.avatar} className="rounded-circle participant-avatar participant-avatar-sm img-thumbnail" alt={'Avatar for ' + assignee.name} />
                </div>
                <div className="d-flex flex-column justify-content-between" style={{ flexGrow: "1" }}>
                    <div className="d-md-flex" style={{ gap: "1rem", flexGrow: "1" }}>
                        <div style={{ flexGrow: "1" }}>
                            <div>
                                <b>{assignee.name}</b> {assignee.moderator ? (<span> (Mod)</span>) : null} {' '}
                                <span className="text-muted small">({assignee.badgeId})</span> {' '}
                                <Button variant="link" className="btn-sm" onClick={() => setShowBio(!showBio)}>[Bio]</Button>
                            </div>
                            {showBio ? (<div className="text-muted small py-2">
                                        {assignee.textBio || "This person has no bio."}
                                    </div>)
                                : null }
                            {assignee.registered ? undefined : (<div className="text-muted small">{assignee.name} is not registered.</div>)}
                        </div>
                        <div style={{ flexGrow: "1" }}>
                            {assignee.interestResponse ? (<div><i>Rank: </i> {assignee.interestResponse.rank}</div>) : undefined}
                            {assignee.interestResponse ? (<div className="small">{assignee.interestResponse.comments}</div>) : undefined}
                        </div>
                    </div>
                    <div className="text-right">
                        <Button variant="link" className="btn-sm" onClick={() => updateModeratorStatus(session?.sessionId, assignee?.badgeId, !assignee?.moderator) }>{assignee.moderator ? 'Unmake Moderator' : 'Make Moderator'}</Button>
                        <Button variant="link" className="btn-sm text-danger">Remove</Button>
                    </div>
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

export default connect(mapStateToProps)(AssignmentCard);