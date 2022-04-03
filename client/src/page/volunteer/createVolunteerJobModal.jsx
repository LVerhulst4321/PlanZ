import axios from 'axios';
import React from 'react';
import { Alert, Form } from 'react-bootstrap';
import Modal from 'react-bootstrap/Modal';
import { connect } from 'react-redux';
import LoadingButton from '../../common/loadingButton';
import store from '../../state/store';
import { fetchJobs, showCreateJobModal } from '../../state/volunteerActions';

class CreateVolunteerJobModal extends React.Component {

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
                            checked={this.getFormValue("online")}
                            onChange={(e) => this.setFormValue("online", e.target.checked)}
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

    isFieldInError(name) {
        let errors = this.state.errors;
        if (errors) {
            return errors[name];
        } else {
            return false;
        }
    }

    getFormValue(formName) {
        if (this.state.values) {
            return this.state.values[formName] || '';
        } else {
            return '';
        }
    }

    setFormValue(formName, formValue) {
        let state = this.state;
        let value = state.values || {};
        let newValue = { ...value };
        let errors = this.state.errors || {};
        newValue[formName] = formValue;
        errors[formName] = !this.validateValue(formName, formValue);

        this.setState((state) => ({
            ...state,
            values: newValue,
            message: null,
            errors: errors
        }));
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

    getErrorClass(name) {
        return this.isFieldInError(name) ? "is-invalid" : "";
    }

    isFieldInError(name) {
        let errors = this.state.errors;
        if (errors) {
            return errors[name];
        } else {
            return false;
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

    isValidForm() {
        let formKeys = ["name", "description"];
        let errors = this.state.errors || {};
        let valid = true
        formKeys.forEach(element => {
            let v = this.validateValue(element, this.state.values[element]);
            valid &= v;
            errors[element] = !v;
        });

        let message = null;
        if (!valid) {
            message = { severity: "danger", text: "Gosh willikers! It looks like some of this information isn't right."}
        }
        this.setState({
            ...this.state,
            errors: errors,
            message: message
        })
        return valid;
    }

    getAllFormValues() {
        return this.state.values;
    }    
}

function mapStateToProps(state) {
    return { showModal: state.volunteering.jobs.showModal };
}
export default connect(mapStateToProps)(CreateVolunteerJobModal);