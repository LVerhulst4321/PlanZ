import React from "react";
import Card from "react-bootstrap/Card";

export const AssignmentCard = ({assignee}) => {
    return (<Card>
        <Card.Body>
            <Card.Title>{assignee.name} <span className="text-muted small">({assignee.badgeId})</span></Card.Title>
            <div className="text-center p-3">
                <img src={assignee.links.avatar} className="rounded-circle participant-avatar participant-avatar-sm img-thumbnail" alt={'Avatar for ' + assignee.name} />
            </div>
            <div>
                {assignee.registered ? undefined : (<div className="text-muted small">{assignee.name} is not registered.</div>)}
                {assignee.interestResponse ? (<div><i>Rank: </i> {assignee.interestResponse.rank}</div>) : undefined}
                {assignee.interestResponse ? (<div><small>{assignee.interestResponse.comments}</small></div>) : undefined}
            </div>
        </Card.Body>
    </Card>);
}