import React from 'react';
import { connect } from 'react-redux';
import { fetchMyShiftAssignments } from '../../state/volunteerFunctions';

import dayjs from "dayjs";
import utc from 'dayjs/plugin/utc';
import timezone from 'dayjs/plugin/timezone';
import advancedFormat from "dayjs/plugin/advancedFormat"
import customParseFormat from "dayjs/plugin/customParseFormat"
dayjs.extend(utc);
dayjs.extend(timezone);
dayjs.extend(customParseFormat);
dayjs.extend(advancedFormat);

class VolunteerSignUpPage extends React.Component {

    componentDidMount() {
        if (this.props.shifts.loading) {
            fetchMyShiftAssignments()
        }
    }

    render() {
        let shifts = (<p>You do not currently have any shifts.</p>);
        if (this.props.shifts && this.props.shifts.list) {
            shifts = this.props.shifts.list.map((s,i) => {
                let from = dayjs(s.fromTime);
                let to = dayjs(s.toTime);
                if (this.props.shifts.context && this.props.shifts.context.timezone) {
                    from = dayjs(s.fromTime).tz(this.props.shifts.context.timezone);
                    to = dayjs(s.toTime).tz(this.props.shifts.context.timezone);
                }
                let fromString = from.format("ddd h:mm a");
                let toString = from.format("ddd h:mm a z");
                if (from.format("ddd") === to.format("ddd")) {
                    toString = to.format("h:mm a z");
                }
        
                return (<div className="mb-3" key={'shift-' + i}>
                    <div><b>{s.job.name}:</b> {fromString}&ndash;{toString}</div>
                    <div><b>Where:</b> {s.location}</div>
                    <div>{s.job.description}</div>
                </div>);
            })
        }
        return (
            <div className="container">
                <div className="card mb-3">
                    <div className="card-header">
                        <div className="d-flex justify-content-between">
                            <h4 className="mr-3">Volunteer Shift Sign-up</h4>
                            <button className="btn btn-primary">Add Shift</button>
                        </div>
                    </div>
                    <div className="card-body">
                        <p>Fan-run cons are only possible because of the incredible effort of volunteers. Please consider signing up for a volunteer shift.</p>
                        <h5>Your Shifts</h5>
                        {shifts}
                    </div>
                </div>
            </div>
        )
    }
}

function mapStateToProps(state) {
    return { 
        shifts: state.volunteering.assignments || {}
    };
}

export default connect(mapStateToProps)(VolunteerSignUpPage);