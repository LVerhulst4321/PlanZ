import axios from 'axios';
import React from 'react';
import { Form, Modal, Spinner } from 'react-bootstrap';
import { connect } from 'react-redux';
import LoadingButton from '../../common/loadingButton';
import SimpleAlert from '../../common/simpleAlert';
import { fetchMyShiftAssignments, fetchShifts } from '../../state/volunteerFunctions';
import { renderDateRange } from '../../util/dateUtil';

import dayjs from "dayjs";
import utc from 'dayjs/plugin/utc';
import timezone from 'dayjs/plugin/timezone';
import advancedFormat from "dayjs/plugin/advancedFormat"
import customParseFormat from "dayjs/plugin/customParseFormat"
import { formatDay } from '../../util/dateUtil';
dayjs.extend(utc);
dayjs.extend(timezone);
dayjs.extend(customParseFormat);
dayjs.extend(advancedFormat);

class VolunteerSignUpAddModal extends React.Component {

    constructor(props) {
        super(props);
        this.state = {
            loadingId: null,
            message: null,
            filter: {
                day: "",
                job: ""
            }
        }
    }

    componentDidUpdate(prevProps, prevState) {
        if (prevProps.show === false && this.props.show === true) {
            this.setState((state) => ({...state, 
                loadingId: null,
                message: null,
                filter: {
                    day: "",
                    job: ""
                }
            }));
            fetchShifts();
        }
    }

    renderFilterSection() {
        let dayOptions = this.props.days ? this.props.days.map((d) => { return (<option value={d} key={'day-' + d}>{formatDay(d)}</option>)}) : undefined;
        let jobOptions = this.props.jobs ? this.props.jobs.map((j) => { return (<option value={j.id} key={j.id}>{j.name}</option>); }) : undefined;

        return (
            <fieldset className="border p-3 mb-3 rounded">
            <legend className="w-auto px-2 text-muted small">Filter</legend>
            <div className="row">
                <Form.Group className="col-md-3 col-md-6" controlId="job">
                    <Form.Label className="sr-only">Job</Form.Label>
                    <Form.Control as="select" onChange={(e) => this.filterJob(e.target.value)} value={this.getFilterJob()}>
                        <option value="">All jobs</option>
                        {jobOptions}
                    </Form.Control>
                </Form.Group>
                <Form.Group className="col-md-3 col-md-6" controlId="day">
                    <Form.Label className="sr-only">Day</Form.Label>
                    <Form.Control as="select" onChange={(e) => this.filterDay(e.target.value)} value={this.getFilterDay()}>
                        <option value="">All days</option>
                        {dayOptions}
                    </Form.Control>
                </Form.Group>
            </div>
        </fieldset>                
        );
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
                {this.renderFilterSection()}
                {list}
            </Modal.Body>
        </Modal>);
    }

    renderListOfShifts(shifts) {
        return shifts.filter(s => !this.isExistingAssignment(s) && this.matchesFilter(s)).map((s, i) => {
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

    matchesFilter(shift) {
        let matches = true;
        let filterDay = this.getFilterDay();
        let filterJob = this.getFilterJob();

        if (filterDay === "") {
            // skip it
        } else if (!(dayjs(shift.fromTime).format('YYYY-MM-DD') === filterDay) &&
            !(dayjs(shift.toTime).format('YYYY-MM-DD') === filterDay)) {

            matches = false;
        } 

        if (filterJob === "" || !matches) {
            // skip it
        } else if (filterJob != shift.job.id) {
            matches = false;
        }

        return matches;
    }

    filterDay(day) {
        this.setState((state) => ({...state, filter: {...state.filter, day: day }}));
    }

    filterJob(job) {
        this.setState((state) => ({...state, filter: {...state.filter, job: job }}));
    }

    getFilterDay() {
        return this.state.filter && this.state.filter.day != null ? this.state.filter.day : "";
    }

    getFilterJob() {
        return this.state.filter && this.state.filter.job != null ? this.state.filter.job : "";
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
        shifts: state.volunteering.shifts || {},
        days: state.volunteering.shifts.context ? state.volunteering.shifts.context.days : [],
        jobs: state.volunteering.jobs ? state.volunteering.jobs.list : []
    };
}

export default connect(mapStateToProps)(VolunteerSignUpAddModal);