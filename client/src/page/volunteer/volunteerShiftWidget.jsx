import React from 'react';
import { connect } from 'react-redux';

class VolunteerShiftWidget extends React.Component {

    render() {
        return (<div>
            <h4>Schedule Volunteer Shifts</h4>
        </div>);
    }
}

function mapStateToProps(state) {
    return { shifts: state.volunteering.shifts };
}

export default connect(mapStateToProps)(VolunteerShiftWidget);