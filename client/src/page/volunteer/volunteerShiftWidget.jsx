import React from 'react';
import { Spinner } from 'react-bootstrap';
import { connect } from 'react-redux';
import store from '../../state/store';
import { showCreateShiftModal } from '../../state/volunteerActions';
import { fetchShifts } from '../../state/volunteerFunctions';
import VolunteerShiftCard from './volunteerShiftCard';

class VolunteerShiftWidget extends React.Component {

    componentDidMount() {
        if (this.props.shifts.loading) {
            fetchShifts();
        }
    }
    render() {
        return (<div>
            <div className="d-flex mb-2 align-items-baseline">
                    <h4 className="mr-3 mb-0">Schedule Volunteer Shifts</h4>
                    <button className="btn btn-link" onClick={(e) => {this.openCreateModal()}}><i className="bi bi-plus-circle"></i></button>
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
            let shifts = this.props.shifts.list.map(s => {
                return (<VolunteerShiftCard shift={s} key={'shift-' + s.id} />);
            });
            return (
                <div className="row row-cols-1 row-cols-lg-3 mt-3">
                    {shifts}
                </div>
            );
        }
    }

    openCreateModal() {
        store.dispatch(showCreateShiftModal(true));
    }
}

function mapStateToProps(state) {
    return { shifts: state.volunteering.shifts };
}

export default connect(mapStateToProps)(VolunteerShiftWidget);