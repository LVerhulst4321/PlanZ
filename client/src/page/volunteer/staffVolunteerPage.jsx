import React from 'react';
import CreateVolunteerJobModal from './createVolunteerJobModal';
import VolunteerJobsWidget from './volunteerJobsWidget';
import VolunteerShiftWidget from './volunteerShiftWidget';

class StaffVolunteerPage extends React.Component {

    render() {
        return (
            <div>
                <div className="row">
                    <div className="col-xl-7">
                        <div className="card mb-3">
                            <div className="card-body">
                                <VolunteerShiftWidget />

                            </div>
                        </div>
                    </div>
                    <div className="col-xl-5">
                        <div className="card mb-3">
                            <div className="card-body">
                                <VolunteerJobsWidget />
                            </div>
                        </div>
                    </div>
                </div>
                <CreateVolunteerJobModal />
            </div>
        )
    }
}

export default StaffVolunteerPage;