import axios from 'axios';
import React from 'react';
import { Alert, Form } from 'react-bootstrap';
import Modal from 'react-bootstrap/Modal';
import { connect } from 'react-redux';
import LoadingButton from '../../common/loadingButton';
import store from '../../state/store';
import { showCreateShiftModal } from '../../state/volunteerActions';
import { fetchShifts } from '../../state/volunteerFunctions';
import FormComponent from './formComponent';

import dayjs from "dayjs";
import utc from 'dayjs/plugin/utc';
import timezone from 'dayjs/plugin/timezone';
import advancedFormat from "dayjs/plugin/advancedFormat"
import customParseFormat from "dayjs/plugin/customParseFormat"
dayjs.extend(utc);
dayjs.extend(timezone);
dayjs.extend(customParseFormat);
dayjs.extend(advancedFormat);

class CreateVolunteerShiftModal extends FormComponent {

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
        let jobOptions = this.props.jobs ? this.props.jobs.map((j) => { return (<option value={j.id} key={j.id}>{j.name}</option>); }) : undefined;
        let dayOptions = this.props.days ? this.props.days.map((d) => { return (<option value={d} key={'day-' + d}>{this.formatDay(d)}</option>)}) : undefined;
        return (
            <Modal show={this.props.showModal} onHide={() => this.handleClose()} size="lg">
                <Modal.Header closeButton>
                <Modal.Title>Create Volunteer Shift</Modal.Title>
                </Modal.Header>
                <Modal.Body>
                    {message}

                    <Form.Group className="mb-3" controlId="job">
                        <Form.Label className="sr-only">Job</Form.Label>
                        <Form.Control as="select" onChange={(e) => this.setFormValue("job", e.target.value)} className={this.getErrorClass('job')} value={this.getFormValue("job")}>
                            <option value="">Select job...</option>
                            {jobOptions}
                        </Form.Control>
                    </Form.Group>
                    <div className="row align-items-center mb-3">
                        <div className="col-md-2 pb-2">Needs</div>
                        <Form.Group className="col-md-2" controlId="min">
                            <Form.Label className="sr-only">Min Count</Form.Label>
                            <Form.Control type="text" className={this.getErrorClass('min')} placeholder="e.g. 1"  
                                value={this.getFormValue("min")} onChange={(e) => this.setFormValue("min", e.target.value)}/>
                        </Form.Group>
                        <div className="col-md-1 pb-2">to</div>
                        <Form.Group className="col-md-2" controlId="max">
                            <Form.Label className="sr-only">Max Count</Form.Label>
                            <Form.Control type="text" className={this.getErrorClass('max')} placeholder="e.g. 5"  
                                value={this.getFormValue("max")} onChange={(e) => this.setFormValue("max", e.target.value)}/>
                        </Form.Group>
                        <div className="col-md-5 pb-2">volunteers</div>
                    </div>
                    <div className="row align-items-end">
                        <Form.Group className="mb-3 col-md-6" controlId="fromDay">
                            <Form.Label>From</Form.Label>
                            <Form.Control as="select" onChange={(e) => this.setFormValue("fromDay", e.target.value)} className={this.getErrorClass('fromDay')} value={this.getFormValue("fromDay")}>
                                <option value="">Select day...</option>
                                {dayOptions}
                            </Form.Control>
                        </Form.Group>
                        <Form.Group className="mb-3 col-md-3" controlId="fromTime">
                            <Form.Label className="sr-only">Time</Form.Label>
                            <Form.Control type="text" className={this.getErrorClass('fromTime')} placeholder="Time..."  
                                value={this.getFormValue("fromTime")} onChange={(e) => this.setFormValue("fromTime", e.target.value)}/>
                        </Form.Group>
                        <Form.Group className="mb-3 col-md-3" controlId="fromDay">
                            <Form.Label className="sr-only">AM/PM</Form.Label>
                            <Form.Control as="select" onChange={(e) => this.setFormValue("fromAmPm", e.target.value)} className={this.getErrorClass('fromAmPm')} value={this.getFormValue("fromAmPm")}>
                                <option value="">Select...</option>
                                <option>AM</option>
                                <option>PM</option>
                            </Form.Control>
                        </Form.Group>
                    </div>
                    <div className="row  align-items-end">
                        <Form.Group className="mb-3 col-md-6" controlId="fromDay">
                            <Form.Label>To</Form.Label>
                            <Form.Control as="select" onChange={(e) => this.setFormValue("toDay", e.target.value)} className={this.getErrorClass('toDay')} value={this.getFormValue("toDay")}>
                                <option value="">Select day...</option>
                                {dayOptions}
                            </Form.Control>
                        </Form.Group>
                        <Form.Group className="mb-3 col-md-3" controlId="fromTime">
                            <Form.Label className="sr-only">Time</Form.Label>
                            <Form.Control type="text" className={this.getErrorClass('toTime')} placeholder="Time..."  
                                value={this.getFormValue("toTime")} onChange={(e) => this.setFormValue("toTime", e.target.value)}/>
                        </Form.Group>
                        <Form.Group className="mb-3 col-md-3" controlId="fromDay">
                            <Form.Label className="sr-only">AM/PM</Form.Label>
                            <Form.Control as="select" onChange={(e) => this.setFormValue("toAmPm", e.target.value)} className={this.getErrorClass('toAmPm')} value={this.getFormValue("toAmPm")}>
                                <option value="">Select...</option>
                                <option>AM</option>
                                <option>PM</option>
                            </Form.Control>
                        </Form.Group>
                    </div>
                    <Form.Group className="mb-3" controlId="location">
                        <Form.Label className="sr-only">Location</Form.Label>
                        <Form.Control type="text" className={this.getErrorClass('location')} placeholder="Location..."  
                            value={this.getFormValue("location")} onChange={(e) => this.setFormValue("location", e.target.value)}/>
                    </Form.Group>

                </Modal.Body>
                <Modal.Footer>
                    <LoadingButton loading={this.state.loading} type="submit" variant="primary" enabled={true} onClick={() => this.submitForm()}>Create</LoadingButton>
                </Modal.Footer>
            </Modal>);
    }

    formatDay(day) {
        return dayjs(day).format('dddd (MMM D)');
    }

    handleClose() {
        store.dispatch(showCreateShiftModal(false));
    }

    validateValue(formName, formValue) {
        if (formName === 'job') {
            return formValue != null && formValue !== '';
        } else if (formName === 'location') {
            return formValue != null && formValue != '';
        } else if (formName === 'fromDay') {
            return formValue != null && formValue != '';
        } else if (formName === 'toDay') {
            if (formValue != null && formValue !== '') {
                if (this.state.values['fromDay'] != null && this.state.values['fromDay'] != "") {
                    return this.state.values['fromDay'].localeCompare(formValue) <= 0;
                } else {
                    return true;
                }
            } else {
                return false;
            }
        } else if (formName === 'fromAmPm' || formName === 'toAmPm') {
            return formValue != null && formValue != '';
        } else if (formName === 'fromTime' || formName === 'toTime') {
            return new RegExp('^([0]?[0-9]|1[0-2])(:[0-5][0-9])?$').test(formValue);
        } else if (formName === 'max' || formName === 'min') {
            if (formValue != null && formValue !== '') {
                return new RegExp('^[0-9]+$').test(formValue) && parseInt(formValue) > 0;
            } else {
                return false;
            }
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

            axios.post('/api/volunteer/create_volunteer_shift.php', this.getAllFormValues())
            .then(res => {
                this.setState({
                    ...this.state,
                    values: {},
                    errors: {},
                    loading: false,
                    message: null
                });
                store.dispatch(showCreateShiftModal(false));
                fetchShifts();
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

    isValidForm() {
        let valid = super.isValidForm();
        if (valid) {
            let formDate = this.getDateFromParts('from');
            let toDate = this.getDateFromParts('to');

            if (formDate.localeCompare(toDate) >= 0) {
                valid = false;
                let errors = this.state.errors;
                errors['toDay'] = true;
                errors['toTime'] = true;
                errors['toAmPm'] = true;

                let message = { severity: "danger", text: "The 'from' date must be earlier than the 'to' date."}
                this.setState((state) => ({
                    ...state,
                    errors: errors,
                    message: message
                }));
            }
        }
        return valid;
    }

    getDateFromParts(prefix) {
        let time = this.state.values[prefix + 'Time'];
        if (time.length === 1) {
            time = '0' + time + ':00';
        } else if (time.length === 2) {
            time = time += ':00';
        } else if (time.length === 4) {
            time = '0' + time;
        }

        let date = this.state.values[prefix + 'Day'] + ' ' + time + ' ' + this.state.values[prefix + 'AmPm'];
        return dayjs.tz(date, 'YYYY-MM-DD hh:mm A', this.props.timezone).toISOString();
    }

    getAllFormValues() {
        return {
            job: this.state.values['job'],
            location: this.state.values['location'],
            fromTime: this.getDateFromParts('from'),
            toTime: this.getDateFromParts('to'),
            minPeople: this.state.values['min'],
            maxPeople: this.state.values['max'],
        };
    }

    getFormFields() {
        return ["job", "location", "fromDay", "fromTime", "fromAmPm", "toDay", "toTime", "toAmPm", "min", "max"];
    }
}

function mapStateToProps(state) {
    return { 
        showModal: state.volunteering.shifts.showModal, 
        days: state.volunteering.shifts.context ? state.volunteering.shifts.context.days : [],
        timezone: state.volunteering.shifts.context ? state.volunteering.shifts.context.timezone : null,
        jobs: state.volunteering.jobs ? state.volunteering.jobs.list : []
    };
}
export default connect(mapStateToProps)(CreateVolunteerShiftModal);