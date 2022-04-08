import React from 'react';
import { connect } from 'react-redux';

import { renderDateRange } from '../../util/dateUtil';

class VolunteerShiftCard extends React.Component {

    render() {
        return (<div className="col mb-4">
            <div className="card border-primary visible-on-hover">
                <div className="card-header bg-primary text-white">
                    <div className="d-flex align-items-top justify-content-between">
                        <div className="mb-1">
                            <b>{this.props.shift.job.name}:</b> {renderDateRange(this.props.shift.fromTime, null, this.props.timezone)}
                        </div>
                        <button className="btn p-0" onClick={() => {}}><i className="bi bi-pencil text-white"></i></button>
                    </div>
                </div>
                <div className="card-body">
                    <div className="row">
                        <div className="col-md-9">
                            <div><b>Shift:</b> {renderDateRange(this.props.shift.fromTime, this.props.shift.toTime, this.props.timezone)}</div>
                            <div><b>Needs:</b> {this.props.shift.minPeople}&ndash;{this.props.shift.maxPeople} volunteers</div>
                            <div><b>Where:</b> {this.props.shift.location}</div>
                        </div>
                        <div className="col-md-3">
                            <div className="text-center small">Sign-ups:</div>
                            <div className="h3 text-primary text-center">{this.props.shift.currentSignupCount}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>)
    }
}

function mapStateToProps(state) {
    return { timezone: state.volunteering.shifts.context ? state.volunteering.shifts.context.timezone : null };
}
export default connect(mapStateToProps)(VolunteerShiftCard);
