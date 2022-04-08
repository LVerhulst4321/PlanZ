import axios from 'axios';
import React from 'react';
import { Modal, Spinner } from 'react-bootstrap';
import { connect } from 'react-redux';
import LoadingButton from '../../common/loadingButton';
import SimpleAlert from '../../common/simpleAlert';
import { fetchMyShiftAssignments, fetchShifts } from '../../state/volunteerFunctions';
import { renderDateRange } from '../../util/dateUtil';

class VolunteerSignUpAddModal extends React.Component {

    constructor(props) {
        super(props);
        this.state = {
            loadingId: null,
            message: null
        }
    }

    componentDidUpdate(prevProps, prevState) {
        if (prevProps.show === false && this.props.show === true) {
            fetchShifts();
        }
    }

    render() {
        let list = undefined;
        if (this.props.shifts.loading) {
            list = (<div className="text-center">
                    <Spinner animation="border" />
                </div>);
        } else if (this.props.shifts.list) {
            list = this.renderListOfShifts(this.props.shifts.list);
        }

        return (<Modal show={this.props.show} onHide={() => this.props.onClose()} size="lg">
            <Modal.Header closeButton>
                <Modal.Title>Add Shift</Modal.Title>
            </Modal.Header>
            <Modal.Body>
                <SimpleAlert message={this.state.message} />
                <p>Select a shift to add it to your schedule</p>
                {list}
            </Modal.Body>
        </Modal>);
    }

    renderListOfShifts(shifts) {
        return shifts.filter(s => !this.isExistingAssignment(s)).map((s, i) => {
            let emphasis = this.highNeed(s);
            return (<div className={'card mb-3 ' + (emphasis ? 'border-primary' : '')} key={'shift-' + s.id}>
                <div className="card-body">
                    <div className="row">
                        <div className="col-md-6"><b>{s.job.name}:</b> {renderDateRange(s.fromTime, s.toTime, this.props.shifts.context.timestamp)}</div>                        
                        <div className="col-md-6"><b>Location:</b> {s.location} </div>
                    </div>
                    <div className="row">
                        <div className="col-md-6"><b>Needs:</b> {s.minPeople}&ndash;{s.maxPeople} volunteers <span>(has {s.currentSignupCount})</span></div>
                        <div className="col-md-6 text-right"><LoadingButton onClick={() => this.addShiftToMyAssignments(s)} enabled={true} 
                            loading={this.state.loadingId == s.id}>Add</LoadingButton></div>
                    </div>
                </div>
            </div>)
        });
    }

    addShiftToMyAssignments(shift) {
        this.setState((state) => ({
            ...state,
            loadingId: state.id
        }));

        axios.post('/api/volunteer/my_shift_assignments.php', { shiftId: shift.id})
        .then(res => {
            this.setState({
                ...this.state,
                loadingId: null,
                message: null
            });
            fetchMyShiftAssignments();
            this.props.onClose();
        })
        .catch(error => {
            console.log(error);
            this.setState({
                ...this.state,
                loadingId: null,
                message: {
                    severity: "danger",
                    text: "Sorry. We've had a bit of a technical problem. Try again?"
                }
            });
        });
    }

    isExistingAssignment(shift) {
        let result = false;
        this.props.assignments.list.forEach(a => result |= (a.id == shift.id));
        return result;
    }

    highNeed(shift) {
        return shift.currentSignupCount < shift.minPeople;
    }
}

function mapStateToProps(state) {
    return { 
        assignments: state.volunteering.assignments,
        shifts: state.volunteering.shifts || {}
    };
}

export default connect(mapStateToProps)(VolunteerSignUpAddModal);