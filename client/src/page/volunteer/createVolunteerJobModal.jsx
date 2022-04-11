import axios from 'axios';
import React from 'react';
import { Alert, Form } from 'react-bootstrap';
import Modal from 'react-bootstrap/Modal';
import { connect } from 'react-redux';
import LoadingButton from '../../common/loadingButton';
import store from '../../state/store';
import { showCreateJobModal } from '../../state/volunteerActions';
import { fetchJobs, fetchShifts } from '../../state/volunteerFunctions';
import FormComponent from './formComponent';

class CreateVolunteerJobModal extends FormComponent {

    constructor(props) {
        super(props);
        this.state = {
            confirmDelete: false,
            loading: false,
            values: {},
            errors: {}
        }
    }

    componentDidUpdate(prevProps, prevState) {
        if (prevProps.showModal === false && this.props.showModal === true) {
            if (this.props.selectedJob == null) {
                this.setState((state) => ({
                    ...state,
                    values: {},
                    errors: {},
                    confirmDelete: false,
                    message: null
                }));
            } else {
                this.setState((state) => ({
                    ...state,
                    values: {
                        name: this.props.selectedJob.name,
                        description: this.props.selectedJob.description,
                        isOnline: this.props.selectedJob.isOnline
                    },
                    errors: {},
                    confirmDelete: false,
                    message: null
                }));
            }
        }
    }

    render() {
        let message = this.state.message ? (<Alert variant={this.state.message.severity}>{this.state.message.text}</Alert>) : undefined;

        let buttons = (<LoadingButton loading={this.state.loading} variant="primary" enabled={true} onClick={() => this.submitJob()}>Create</LoadingButton>);
        if (this.state.confirmDelete) {
            buttons = [
                <button className="btn btn-link" onClick={() => this.setState((state) => ({...state, confirmDelete: false }))} key="btn-cancel">Cancel</button>,
                <LoadingButton loading={this.state.loading} variant="danger" enabled={true} onClick={() => this.deleteJob()} key="btn-delete">Confirm Delete</LoadingButton>];
        } else if (this.props.selectedJob != null) {
            buttons = [
                <button className="btn btn-outline-danger" onClick={() => this.setState((state) => ({...state, confirmDelete: true }))} key="btn-delete">Delete</button>,
                <LoadingButton loading={this.state.loading} variant="primary" enabled={true} onClick={() => this.submitJob()} key="btn-save">Save</LoadingButton>];
        }

        let confirmMessage = this.state.confirmDelete 
            ? (<p>Are you sure you want to delete this job? All related shifts will also be deleted.</p>) 
            : undefined;

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

                    {confirmMessage}
                </Modal.Body>
                <Modal.Footer>
                    {buttons}
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

    submitJob() {
        if (this.isValidForm()) {
            this.setState((state) => ({
                ...state,
                loading: true,
                message: null
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

    deleteJob() {
        if (this.isValidForm()) {
            this.setState((state) => ({
                ...state,
                loading: true,
                message: null
            }));

            axios.delete('/api/volunteer/create_volunteer_job.php', {
                headers: {},
                data: {
                    "id": this.props.selectedJob.id
                }
            })
            .then(res => {
                this.setState({
                    ...this.state,
                    values: {},
                    errors: {},
                    loading: false,
                    message: null,
                    confirmDelete: false
                });
                store.dispatch(showCreateJobModal(false));
                fetchJobs();
                fetchShifts();
            })
            .catch(error => {
                console.log(error);
                this.setState({
                    ...this.state,
                    loading: false,
                    confirmDelete: false,
                    message: {
                        severity: "danger",
                        text: "Sorry. We've had a bit of a technical problem. Try again?"
                    }
                });
            });
        }
    }

    getFormFields() {
        return ["name", "description", "isOnline"];
    }

    getAllFormValues() {
        let values = super.getAllFormValues();
        if (this.props.selectedJob != null) {
            values['id'] = this.props.selectedJob.id;
        }
        return values;
    }
}

function mapStateToProps(state) {
    return { showModal: state.volunteering.jobs.showModal, selectedJob: state.volunteering.jobs.selectedJob };
}
export default connect(mapStateToProps)(CreateVolunteerJobModal);