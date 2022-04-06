import axios from 'axios';
import React from 'react';
import { Alert, Form } from 'react-bootstrap';
import Modal from 'react-bootstrap/Modal';
import { connect } from 'react-redux';
import LoadingButton from '../../common/loadingButton';
import store from '../../state/store';
import { showCreateJobModal } from '../../state/volunteerActions';
import { fetchJobs } from '../../state/volunteerFunctions';
import FormComponent from './formComponent';

class CreateVolunteerJobModal extends FormComponent {

    constructor(props) {
        super(props);
        this.state = {
            loading: false,
            values: {},
            errors: {}
        }
    }

    render() {
        let message = this.state.message ? (<Alert variant={this.state.message.severity}>{this.state.message.text}</Alert>) : undefined;
        return (
            <Modal show={this.props.showModal} onHide={() => this.handleClose()} size="lg">
                <Modal.Header closeButton>
                <Modal.Title>Create Volunteer Job</Modal.Title>
                </Modal.Header>
                <Modal.Body>
                    {message}

                    <Form.Group className="mb-3" controlId="name">
                        <Form.Label className="sr-only">Name</Form.Label>
                        <Form.Control type="text" className={this.getErrorClass('name')} placeholder="Job name..." value={this.getFormValue("name")} 
                            onChange={(e) => this.setFormValue("name", e.target.value)}/>
                    </Form.Group>
                    <Form.Group className="mb-3" controlId="isOnline">
                        <Form.Check
                            type="checkbox"
                            label="Is this an online job?"
                            checked={this.getFormValue("isOnline")}
                            onChange={(e) => this.setFormValue("isOnline", e.target.checked)}
                        />
                    </Form.Group>
                    <Form.Group className="mb-3" controlId="description">
                        <Form.Label className="sr-only">Description</Form.Label>
                        <Form.Control as="textarea" rows={3} className={this.getErrorClass('description')} placeholder="Description..."  
                            value={this.getFormValue("description")} onChange={(e) => this.setFormValue("description", e.target.value)}/>
                    </Form.Group>

                </Modal.Body>
                <Modal.Footer>
                    <LoadingButton loading={this.state.loading} type="submit" variant="primary" enabled={true} onClick={() => this.submitForm()}>Create</LoadingButton>
                </Modal.Footer>
            </Modal>);
    }

    handleClose() {
        store.dispatch(showCreateJobModal(false));
    }

    validateValue(formName, formValue) {
        if (formName === 'name') {
            return formValue != null && formValue !== '';
        } else if (formName === 'description') {
            return formValue != null && formValue != '';
        } else {
            return true;
        }
    }

    submitForm(event) {
        if (event) {
            event.preventDefault();
            event.stopPropagation();
        }

        if (this.isValidForm()) {
            this.setState((state) => ({
                ...state,
                loading: true
            }));

            axios.post('/api/volunteer/create_volunteer_job.php', this.getAllFormValues())
            .then(res => {
                this.setState({
                    ...this.state,
                    values: {},
                    errors: {},
                    loading: false,
                    message: null
                });
                store.dispatch(showCreateJobModal(false));
                fetchJobs();
            })
            .catch(error => {
                console.log(error);
                this.setState({
                    ...this.state,
                    loading: false,
                    message: {
                        severity: "danger",
                        text: "Sorry. We've had a bit of a technical problem. Try again?"
                    }
                });
            });
        }
    }

    getFormFields() {
        return ["name", "description"];
    }
}

function mapStateToProps(state) {
    return { showModal: state.volunteering.jobs.showModal };
}
export default connect(mapStateToProps)(CreateVolunteerJobModal);