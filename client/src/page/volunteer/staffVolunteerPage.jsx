import React from 'react';
import CreateVolunteerJobModal from './createVolunteerJobModal';
import CreateVolunteerShiftModal from './createVolunteerShiftModal';
import VolunteerJobsWidget from './volunteerJobsWidget';
import VolunteerShiftWidget from './volunteerShiftWidget';

class StaffVolunteerPage extends React.Component {

    render() {
        return (
            <div>
                <div className="row">
                    <div className="col-xl-8">
                        <div className="card mb-3">
                            <div className="card-body">
                                <VolunteerShiftWidget />

                            </div>
                        </div>
                    </div>
                    <div className="col-xl-4">
                        <div className="card mb-3">
                            <div className="card-body">
                                <VolunteerJobsWidget />
                            </div>
                        </div>
                    </div>
                </div>
                <CreateVolunteerJobModal />
                <CreateVolunteerShiftModal />
            </div>
        )
    }
}

export default StaffVolunteerPage;