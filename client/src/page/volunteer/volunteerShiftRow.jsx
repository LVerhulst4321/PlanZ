import React from 'react';
import { connect } from 'react-redux';
import store from '../../state/store';
import { showCreateShiftModal } from '../../state/volunteerActions';

import { renderDateRange } from '../../util/dateUtil';

class VolunteerShiftRow extends React.Component {

    render() {
        return (<tr className="visible-on-hover">
                    <td><b>{this.props.shift.job.name}</b></td>
                    <td>{renderDateRange(this.props.shift.fromTime, this.props.shift.toTime, this.props.timezone)}</td>
                    <td>{this.renderNeeds(this.props.shift)}</td>
                    <td>{this.props.shift.location}</td>
                    <td className="text-primary text-center">{this.props.shift.currentSignupCount}</td>
                    <td className="text-right"><button className="btn p-0 border-0" onClick={() => { this.openModal()}}><i className="bi bi-pencil text-primary"></i></button></td>
                </tr>)
    }

    renderNeeds(shift) {
        if (shift.minPeople === shift.maxPeople) {
            return (<>{this.props.shift.minPeople}  <small>volunteer</small></>)
        } else {
            return (<>
                {this.props.shift.minPeople}&ndash;{this.props.shift.maxPeople} <small>volunteers</small>
                </>);
        }
    }

    openModal() {
        store.dispatch(showCreateShiftModal(true, this.props.shift));
    }
}

function mapStateToProps(state) {
    return { timezone: state.volunteering.shifts.context ? state.volunteering.shifts.context.timezone : null };
}
export default connect(mapStateToProps)(VolunteerShiftRow);
