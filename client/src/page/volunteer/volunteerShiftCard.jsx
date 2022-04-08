import React from 'react';
import { connect } from 'react-redux';

import { renderDateRange } from '../../util/dateUtil';

class VolunteerShiftCard extends React.Component {

    render() {
        return (<div className="col mb-4">
            <div className="card border-primary">
                <div className="card-header bg-primary text-white">
                    <b>{this.props.shift.job.name}:</b> {renderDateRange(this.props.shift.fromTime, null, this.props.timezone)}
                </div>
                <div className="card-body">
                    <div><b>Shift:</b> {renderDateRange(this.props.shift.fromTime, this.props.shift.toTime, this.props.timezone)}</div>
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
