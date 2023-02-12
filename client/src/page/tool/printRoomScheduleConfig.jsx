import React, { useEffect, useState } from 'react';
import axios from 'axios';
import Button from 'react-bootstrap/Button';
import Spinner from 'react-bootstrap/Spinner';
import SimpleAlert from '../../common/simpleAlert';

const PrintRoomScheduleConfigPage = () => {

    let [loading, setLoading] = useState(true);
    let [message, setMessage] = useState(null);

    useEffect(() => {
        axios.get('/api/tool/scheduled_session_count.php')
        .then(res => {
            setLoading(false);
            if (res.data.count === 0) {
                setMessage({
                    severity: "warning",
                    text: "No sessions are currently scheduled, so we don't expect anything to be printed."
                });
            }
        })
        .catch(error => {
            if (error.response && error.response.status === 401) {
                redirectToLogin();
            } else {
                setMessage({
                    severity: "danger",
                    text: "We've hit a bit of a technical snag trying to get information about that session."
                });
            }
        });
    }, []);

    return (<>
        {loading
        ? (<div className="text-center">
                <Spinner animation="border" role="status">
                    <span className="visually-hidden">Loading...</span>
                </Spinner>
            </div>)
        : null}
        <SimpleAlert message={message} />
        <div className="card">
        <div className="card-header">
            <h2>Print Room Schedule Config</h2>
        </div>
        <div className="card-body">
            <p>
                Many cons post a list of the day's sessions at or on the door of each room.
                This tool can be used to generate the room schedule.
            </p>
            <p>
                Online "rooms" are omitted from the printout.
            </p>
        </div>
        <div className="card-footer text-right">
            <Button variant="primary" onClick={() => {window.open('./printRoomSchedule.php', "_blank");}}>Generate</Button>
        </div>
    </div></>);
}

export default PrintRoomScheduleConfigPage;