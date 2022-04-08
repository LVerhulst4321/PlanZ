import axios from 'axios';
import React from 'react';
import { Modal } from 'react-bootstrap';
import { connect } from 'react-redux';
import LoadingButton from '../../common/loadingButton';
import store from '../../state/store';
import { fetchMyShiftAssignments } from '../../state/volunteerFunctions';

import { renderDateRange } from '../../util/dateUtil';

class VolunteerSignUpPage extends React.Component {


    constructor(props) {
        super(props);
        this.state = {
            showDeleteModal: false,
            deleteLoading: false,
            deleteItem: null,
            showAddModal: false
        };
    }

    componentDidMount() {
        if (this.props.shifts.loading) {
            fetchMyShiftAssignments()
        }
    }

    render() {
        let shifts = (<p>You do not currently have any shifts.</p>);
        if (this.props.shifts && this.props.shifts.list && this.props.shifts.list.length > 0) {
            shifts = this.props.shifts.list.map((s,i) => {
                let dateRange = renderDateRange(s.fromTime, s.toTime, this.props.shifts.context ? this.props.shifts.context.timezone : null);
                return (<div className="mb-4 visible-on-hover" key={'shift-' + i}>
                    <div className="d-flex align-items-center">
                        <div className="mr-3 mb-1"><b>{s.job.name}:</b> {dateRange}</div>
                        <button className="btn p-0" onClick={() => this.showDeleteModal(true, s)}><i className="bi-trash text-danger"></i></button>
                    </div>
                    <div><b>Where:</b> {s.location}</div>
                    <div>{s.job.description}</div>
                </div>);
            })
        }
        return (
            <div>
                <div className="container">
                    <div className="card mb-3">
                        <div className="card-header">
                            <div className="d-flex justify-content-between">
                                <h4 className="mr-3">Volunteer Shift Sign-up</h4>
                                <button className="btn btn-primary" onClick={() => this.showAddModal(true)}>Add Shift</button>
                            </div>
                        </div>
                        <div className="card-body">
                            <p>Fan-run cons are only possible because of the incredible effort of volunteers. Please consider signing up for a volunteer shift.</p>
                            <h5>Your Shifts</h5>
                            {shifts}
                        </div>
                    </div>
                </div>
                <Modal show={this.state.showDeleteModal} onHide={() => this.showDeleteModal(false, null)} size="lg">
                    <Modal.Header closeButton>
                        <Modal.Title>Confirm</Modal.Title>
                    </Modal.Header>
                    <Modal.Body>
                        <p>Are you sure you want to remove this shift from your list?</p>
                    </Modal.Body>
                    <Modal.Footer>
                        <LoadingButton variant="danger" onClick={(e) => this.performDelete()} loading={this.state.deleteLoading} enabled="true">Remove</LoadingButton>
                    </Modal.Footer>
                </Modal>
                <Modal show={this.state.showAddModal} onHide={() => this.showAddModal(false)} size="lg">
                    <Modal.Header closeButton>
                        <Modal.Title>Add Shift</Modal.Title>
                    </Modal.Header>
                    <Modal.Body>
                        <p>Select a shift to add it to your schedule</p>
                    </Modal.Body>
                </Modal>
            </div>
        )
    }

    showAddModal(show) {
        this.setState((state) => ({...state, showAddModal: show }));
    }

    showDeleteModal(show, item) {
        this.setState((state) => ({...state, showDeleteModal: show, deleteItem: item}));
    }

    performDelete() {

        this.setState((state) => ({
            ...state,
            deleteLoading: true
        }));

        axios.delete('/api/volunteer/my_shift_assignments.php', 
            {
                headers: {},
                data: { shiftId: this.state.deleteItem.id}
            })
        .then(res => {
            this.setState({
                ...this.state,
                deleteLoading: false,
                deleteMessage: null
            });
            fetchMyShiftAssignments();
            this.showDeleteModal(false, null);
        })
        .catch(error => {
            this.setState({
                ...this.state,
                deleteLoading: false,
                deleteMessage: {
                    severity: "danger",
                    text: "Sorry. We've had a bit of a technical problem. Try again?"
                }
            });
        });
    }
}

function mapStateToProps(state) {
    return { 
        shifts: state.volunteering.assignments || {}
    };
}

export default connect(mapStateToProps)(VolunteerSignUpPage);