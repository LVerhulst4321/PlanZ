import React from 'react';
import { Spinner } from 'react-bootstrap';
import { connect } from 'react-redux';
import store from '../../state/store';
import { showCreateJobModal } from '../../state/volunteerActions';
import { fetchJobs } from '../../state/volunteerFunctions';

class VolunteerJobsWidget extends React.Component {

    componentDidMount() {
        if (this.props.jobs.loading) {
            fetchJobs();
        }
    }

    render() {
        return (
            <div>
                <div className="d-flex mb-2 align-items-baseline justify-content-between">
                    <h5 className="mr-3 mb-0">Volunteer Jobs</h5>
                    <button className="btn btn-outline-primary" onClick={(e) => {this.openModal()}}>Create Job</button>
                </div>
                {this.renderMain()}
            </div>
        );
    }

    renderMain() {
        if (this.props.jobs.loading) {
            return (
                <div className="text-center">
                    <Spinner animation="border" />
                </div>
            );
        } else if (this.props.jobs.list.length === 0) {
            return (<p>No jobs have been created.</p>)
        } else {
            let jobs = this.props.jobs.list.map(j => {
                let type = j.isOnline ? (<small className="text-nowrap">(Online)</small>) : (<small className="text-nowrap">(In-Person)</small>);
                return (<div className="col mb-4" key={'job-' + j.id}>
                    <div className="card visible-on-hover">
                        <div className="card-header">
                            <div className="d-flex align-items-top justify-content-between">
                                <div className="mb-1">
                                    <b>{j.name}</b> {type}
                                </div>
                                <button className="btn p-0" onClick={() => this.openModal(j)}><i className="bi bi-pencil text-primary"></i></button>
                            </div>
                        </div>
                        <div className="card-body small">
                            {j.description}
                        </div>
                    </div>
                </div>);
            });

            return (
                <div className="row row-cols-1 row-cols-lg-3 mt-3">
                    {jobs}
                </div>
            );
        }
    }

    openModal(job = null) {
        store.dispatch(showCreateJobModal(true, job));
    }
}

function mapStateToProps(state) {
    return { jobs: state.volunteering.jobs };
}
export default connect(mapStateToProps)(VolunteerJobsWidget);