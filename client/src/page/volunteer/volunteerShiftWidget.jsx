import React from 'react';
import { Form, Spinner } from 'react-bootstrap';
import { connect } from 'react-redux';
import store from '../../state/store';
import { showCreateShiftModal } from '../../state/volunteerActions';
import VolunteerShiftCard from './volunteerShiftCard';

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

class VolunteerShiftWidget extends React.Component {

    constructor(props) {
        super(props);
        this.state = {
            filter: {
                day: "",
                job: ""
            }
        }
    }

    render() {
        return (<div>
            <div className="d-flex mb-2 align-items-baseline justify-content-between">
                    <h4 className="mr-3 mb-0">Schedule Volunteer Shifts</h4>
                    <button className="btn btn-primary" onClick={(e) => {this.openCreateModal()}}
                        disabled={!(this.props.jobs && this.props.jobs.length > 0)}>Create Shift</button>
                </div>
            {this.renderMain()}
        </div>);
    }

    renderMain() {
        if (this.props.shifts.loading) {
            return (
                <div className="text-center">
                    <Spinner animation="border" />
                </div>
            );
        } else if (this.props.shifts.list.length === 0) {
            return (<p>No shifts have been created.</p>)
        } else {
            let shifts = this.getFilteredShifts();
            let dayOptions = this.props.days ? this.props.days.map((d) => { return (<option value={d} key={'day-' + d}>{formatDay(d)}</option>)}) : undefined;
            let jobOptions = this.props.jobs ? this.props.jobs.map((j) => { return (<option value={j.id} key={j.id}>{j.name}</option>); }) : undefined;
            return (
                <div>
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

                    <div className="row row-cols-1 row-cols-lg-3 mt-3">
                        {shifts}
                    </div>
                </div>
            );
        }
    }

    getFilteredShifts() {
        return this.props.shifts.list.filter(s => this.matchesFilter(s)).map(s => {
            return (<VolunteerShiftCard shift={s} key={'shift-' + s.id} />);
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
        } else if (filterJob !== shift.job.id) {
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

    openCreateModal() {
        store.dispatch(showCreateShiftModal(true));
    }
}

function mapStateToProps(state) {
    return {
        shifts: state.volunteering.shifts,
        days: state.volunteering.shifts.context ? state.volunteering.shifts.context.days : [],
        jobs: state.volunteering.jobs ? state.volunteering.jobs.list : []
    };
}

export default connect(mapStateToProps)(VolunteerShiftWidget);