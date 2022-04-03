import React from 'react';
import CreateVolunteerJobModal from './createVolunteerJobModal';
import VolunteerJobsWidget from './volunteerJobsWidget';

class StaffVolunteerPage extends React.Component {

    render() {
        return (
            <div>
                <div className="row">
                    <div className="col-md-6">
                        <div className="card">
                            <div className="card-header">
                                <h4>Schedule Volunteer Shifts</h4>
                            </div>
                            <div className="card-body">

                            </div>
                        </div>
                    </div>
                    <div className="col-md-6">
                        <div className="border-left border-light pl-3">
                            <VolunteerJobsWidget />
                        </div>
                    </div>
                </div>
                <CreateVolunteerJobModal />
            </div>
        )
    }
}

export default StaffVolunteerPage;