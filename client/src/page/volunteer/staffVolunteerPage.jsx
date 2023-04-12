import React, { useEffect, useState } from 'react';
import Nav from 'react-bootstrap/Nav';
import { connect } from 'react-redux';
import { fetchJobs, fetchShifts } from '../../state/volunteerFunctions';
import CreateVolunteerJobModal from './createVolunteerJobModal';
import CreateVolunteerShiftModal from './createVolunteerShiftModal';
import VolunteerJobsWidget from './volunteerJobsWidget';
import VolunteerShiftWidget from './volunteerShiftWidget';
import SimpleAlert from '../../common/simpleAlert';

const JOBS = "Jobs";
const SHIFTS = "Shift";
const SIGNUPS = "SignUp";

const StaffVolunteerPage = ({ jobsLoading, shiftsLoading, hasJobs}) => {

    const showBody = (activeTab) => {
        if (activeTab === JOBS) {
            return (<VolunteerJobsWidget />);
        } else if (activeTab === SHIFTS) {
            return (<VolunteerShiftWidget />)
        } else {
            return undefined;
        }
    }

    useEffect(() => {
        if (jobsLoading) {
            fetchJobs();
        }
        if (shiftsLoading) {
            fetchShifts();
        }
    }, [jobsLoading, shiftsLoading])

    let [ activeTab, setActiveTab ] = useState(JOBS)
    let [ isTabManuallyChanged, setTabManuallyChanged ] = useState(false)

    if (!jobsLoading && hasJobs && !isTabManuallyChanged && activeTab === JOBS) {
        setActiveTab(SHIFTS);
    }

    let message = null;
    if (!hasJobs && !jobsLoading) {
        message = {
            severity: "info",
            text: "No jobs have been created. You must create jobs before you can create shifts."
        }
    }

    return (
        <div className="container">
            <SimpleAlert message={message} />
            <div className="card mb-3">
                <div className="card-header">
                    <Nav variant="tabs" activeKey={activeTab} className='card-header-tabs'>
                        <Nav.Item>
                            <Nav.Link eventKey={JOBS} onSelect={() => { setActiveTab(JOBS); setTabManuallyChanged(true) }}>Jobs</Nav.Link>
                        </Nav.Item>
                        <Nav.Item>
                            <Nav.Link eventKey={SHIFTS} onSelect={() => { setActiveTab(SHIFTS); setTabManuallyChanged(true); }}
                                disabled={!hasJobs}>Shifts</Nav.Link>
                        </Nav.Item>
                        <Nav.Item>
                            <Nav.Link eventKey={SIGNUPS} disabled>
                            Sign-ups
                            </Nav.Link>
                        </Nav.Item>
                    </Nav>
                </div>
                <div className="card-body">

                    {showBody(activeTab)}

                </div>
            </div>
            <CreateVolunteerJobModal />
            <CreateVolunteerShiftModal />
        </div>
    )


}

function mapStateToProps(state) {
    return {
        jobsLoading: state.volunteering.jobs.loading,
        shiftsLoading: state.volunteering.shifts.loading,
        hasJobs: state.volunteering.jobs?.list?.length
    };
}

export default connect(mapStateToProps)(StaffVolunteerPage);