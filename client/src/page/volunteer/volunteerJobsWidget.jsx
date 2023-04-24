import React from 'react';
import { Spinner } from 'react-bootstrap';
import { connect } from 'react-redux';
import store from '../../state/store';
import { showCreateJobModal } from '../../state/volunteerActions';

class VolunteerJobsWidget extends React.Component {

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
                let type = j.isOnline ? (<small className="text-nowrap">Online</small>) : (<small className="text-nowrap">In-Person</small>);
                return (<tr key={'job-' + j.id} className="visible-on-hover">
                        <td><b>{j.name}</b></td>
                        <td>{type}</td>
                        <td>{j.description}</td>
                        <td><button className="btn p-0 border-0" onClick={() => this.openModal(j)}><i className="bi bi-pencil text-primary"></i></button></td>
                    </tr>);
            });

            return (
                <table className="table table-striped">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Type</th>
                            <th colSpan={2}>Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        {jobs}
                    </tbody>
                </table>
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