import React, { useState } from "react";
import Button from "react-bootstrap/Button";
import Card from "react-bootstrap/Card";
import { connect } from "react-redux";
import { createAssignment, removeAssignment, updateModeratorStatus } from "../../state/assignmentsFunctions";

const AssignmentCard = ({assignee, assigned, session}) => {
    let [showBio, setShowBio] = useState(false);

    let className = "";
    if (assignee.moderator) {
        className = "border-success";
    }
    if (assignee.conflicts?.length) {
        className += " bg-warning bg-opacity-25";
    }

    return (<Card className={className}>
        <Card.Body className="p-3">

            <div className="d-flex" style={{ gap: "1rem" }}>
                <div style={{ flexGrow: "0", flexShrink: "0" }}>
                    <img src={assignee.links.avatar} className="rounded-circle participant-avatar participant-avatar-sm img-thumbnail" alt={'Avatar for ' + assignee.name} />
                </div>
                <div className="d-flex flex-column justify-content-between" style={{ flexGrow: "1" }}>
                    <div className="row" style={{ flexGrow: "1" }}>
                        <div className="col-md-6">
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
                            {assignee.conflicts?.length ? (<div className="text-muted small">{assignee.name} has conflicts for this time slot.</div>) : undefined}
                            {assignee.willingnessToBeParticipant === 'Unknown'
                                ? (<div className="text-muted small">{assignee.name} has not specified their willingness to be a participant.</div>)
                                : (assignee.willingnessToBeParticipant === 'No'
                                    ? (<div className="text-danger small">{assignee.name} does not want to be on panels.</div>)
                                    : null)}
                        </div>
                        <div className="col-md-6">
                            {assignee.interestResponse ? (<div><i>Rank: </i> {assignee.interestResponse.rank}</div>) : undefined}
                            {assignee.interestResponse ? (<div className="small">{assignee.interestResponse.comments}</div>) : undefined}
                        </div>
                    </div>
                    {assigned
                        ? (<div className="text-right">
                            <Button variant="link" className="btn-sm" onClick={() => updateModeratorStatus(session?.sessionId, assignee?.badgeId, !assignee?.moderator) }>{assignee.moderator ? 'Unmake Moderator' : 'Make Moderator'}</Button>
                            <Button variant="link" className="btn-sm text-danger" onClick={() => removeAssignment(session?.sessionId, assignee?.badgeId)}>Remove</Button>
                        </div>)
                        : (<div className="text-right">
                            <Button variant="link" className="btn-sm" onClick={() => createAssignment(session?.sessionId, assignee?.badgeId) }>Assign</Button>
                        </div>)}
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