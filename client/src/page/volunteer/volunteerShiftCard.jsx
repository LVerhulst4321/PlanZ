import React from 'react';
import { connect } from 'react-redux';

import dayjs from "dayjs";
import utc from 'dayjs/plugin/utc';
import timezone from 'dayjs/plugin/timezone';
import advancedFormat from "dayjs/plugin/advancedFormat"
dayjs.extend(utc);
dayjs.extend(timezone);
dayjs.extend(advancedFormat);

class VolunteerShiftCard extends React.Component {

    render() {
        let from = dayjs(this.props.shift.fromTime);
        let to = dayjs(this.props.shift.toTime);
        if (this.props.timezone) {
            from = dayjs(this.props.shift.fromTime).tz(this.props.timezone);
            to = dayjs(this.props.shift.toTime).tz(this.props.timezone);
        }
        let fromString = from.format("ddd h:mm a");
        let toString = from.format("ddd h:mm a z");
        if (from.format("ddd") === to.format("ddd")) {
            toString = to.format("h:mm a z");
        }

        return (<div className="col mb-4">
            <div className="card border-primary">
                <div className="card-header bg-primary text-white">
                    <b>{this.props.shift.job.name}:</b> {fromString}
                </div>
                <div className="card-body">
                    <div><b>Shift:</b> {fromString}&ndash;{toString}</div>
                    <div><b>Needs:</b> {this.props.shift.minPeople}&ndash;{this.props.shift.maxPeople} volunteers</div>
                    <div><b>Where:</b> {this.props.shift.location}</div>
                </div>
            </div>
        </div>)
    }
}

function mapStateToProps(state) {
    return { timezone: state.volunteering.shifts.context ? state.volunteering.shifts.context.timezone : null };
}
export default connect(mapStateToProps)(VolunteerShiftCard);
