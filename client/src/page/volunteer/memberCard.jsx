import axios from "axios";
import React, { useState } from "react";
import Card from "react-bootstrap/Card";
import LoadingButton from "../../common/loadingButton";
import NameDisplay from "../../common/nameDisplay";
import { redirectToLogin } from "../../common/redirectToLogin";
import { fetchAllShiftAssignments } from "../../state/volunteerFunctions";

const MemberCard = ({candidate, shiftId, closeModal}) => {

    let [loading, setLoading] = useState(false);

    const createVolunteerAssignment = (badgeId) => {
        setLoading(true);
        axios.post('/api/volunteer/volunteer_shift_assignment.php', { shiftId: shiftId, badgeId: badgeId })
        .then(res => {
            setLoading(false);
            fetchAllShiftAssignments();
        })
        .catch(error => {
            if (error.response && error.response.status === 401) {
                redirectToLogin();
            }
        });
    }

    return (<Card className="mb-3">
        <Card.Body className="p-3">

            <div className="d-flex" style={{ gap: "0.5rem" }}>
                <div style={{ flexGrow: "0" }}>
                    <img src={candidate?.links?.avatar} className="rounded-circle participant-avatar participant-avatar-xs img-thumbnail" alt={'Avatar for ' + (candidate.name?.pubsName ?? "Participant")} />
                </div>
                <div className="d-flex flex-column justify-content-between" style={{ flexGrow: "1" }}>
                    <div>
                        <NameDisplay name={candidate?.name} /> {' '}
                        <span className="text-muted small">({candidate?.badgeId})</span>
                    </div>
                </div>
                <div style={{ flexGrow: "0" }}>
                    <LoadingButton variant="outline-primary" size="sm" loading={loading} enabled={true}
                        onClick={() => { createVolunteerAssignment(candidate?.badgeId); closeModal(); } }>Add</LoadingButton>
                </div>
            </div>
        </Card.Body>
    </Card>);
}

export default MemberCard;