import React from 'react';
import Button from 'react-bootstrap/esm/Button';

const PrintRoomScheduleConfigPage = () => {
    return (<div className="card">
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
            <Button variant="primary" onClick={() => {}}>Generate</Button>
        </div>
    </div>);
}

export default PrintRoomScheduleConfigPage;