import React from 'react';
import Modal from 'react-bootstrap/Modal';
import { connect } from 'react-redux';
import store from '../../state/store';
import { showCreateJobModal } from '../../state/volunteerActions';

class CreateVolunteerJobModal extends React.Component {

    render() {
        return (<Modal show={this.props.showModal} onHide={() => this.handleClose(false)} size="lg">
                <Modal.Header closeButton>
                <Modal.Title>Create Volunteer Job</Modal.Title>
                </Modal.Header>
                <Modal.Body>
                    Do the thing.
                </Modal.Body>
            </Modal>);
    }

    handleClose(create) {
        store.dispatch(showCreateJobModal(false));
    }
}

function mapStateToProps(state) {
    console.log("state change: " + state.volunteering.jobs.showModal);
    return { showModal: state.volunteering.jobs.showModal };
}
export default connect(mapStateToProps)(CreateVolunteerJobModal);