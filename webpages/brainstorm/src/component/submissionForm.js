import React, { Component } from 'react';
import axios from 'axios';

import Alert from 'react-bootstrap/Alert';
import Button from 'react-bootstrap/Button';
import Card from 'react-bootstrap/Card';
import Form from 'react-bootstrap/Form';
import Spinner from 'react-bootstrap/Spinner';

import dayjs from "dayjs";
import utc from 'dayjs/plugin/utc';
import timezone from 'dayjs/plugin/timezone';
import advancedFormat from "dayjs/plugin/advancedFormat"
dayjs.extend(utc);
dayjs.extend(timezone);
dayjs.extend(advancedFormat);

import store from '../state/store';

class SubmissionForm extends Component {

    constructor(props) {
        super(props);

        this.state = {
            values: this.createInitialValues(),
            submitAllowed: (store.getState().auth.jwt != null),
            errors: {}
        }
    }

    getDivisions() {
        return this.selectOpenDivisions(store.getState().options.divisions);
    }

    createInitialValues() {
        let divisions = this.getDivisions();
        let values = {};
        if (divisions && divisions.length === 1) {
            values['division'] = divisions[0].id.toString();
            let track = this.selectDefaultTrackForDivision(divisions[0].id);
            if (track) {
                values['track'] = "" + track;
            }
        }
        return values;
    }


    componentDidMount() {
        this.unsubscribe = store.subscribe(() => {
            let values = this.state.values;
            if (values === {}) {
                values = this.createInitialValues();
            }
            let submitAllowed = (store.getState().auth.jwt != null);
            this.setState({
                ...this.state,
                values: values,
                submitAllowed: submitAllowed
            });
        });
    }

    componentWillUnmount() {
        if (this.unsubscribe) {
            this.unsubscribe();
        }
    }

    selectOpenDivisions(divisions) {
        return divisions.filter((d) => {
            let toDate = dayjs(d.to_time);
            let now = dayjs();
            return toDate.diff(now) > 0
        });
    }

    isAcademic(division) {
        return division != null && division.name === 'Academic'
    }

    getFieldList(division) {
        if (this.isAcademic(division)) {
            return ["division", "title", "progguiddesc", "pocketprogtext", "track", "notesforprog", "servicenotes"];
        } else {
            return ["division", "title", "progguiddesc", "track", "servicenotes", "persppartinfo"];
        }
    }

    getSelectedDivision() {
        if (this.state.values['division'] && this.getDivisions()) {
            let divisionId = this.state.values['division'];
            let division = null;
            this.getDivisions().forEach((element) => { if (element.id.toString() === divisionId) { division = element; } } );
            return division;
        } else {
            return null;
        }
    }

    createField(fieldName) {
        if (fieldName === "division") {
            let options = this.getDivisions() ? this.getDivisions().map((d) => { return (<option value={d.id} key={d.id}>{d.name}</option>)}) : undefined;
            return (
                <Form.Group controlId="division" key="division-field">
                    <Form.Label className="sr-only">Division:</Form.Label>
                    <Form.Control as="select" className={this.getErrorClass('division')} value={this.getFormValue('division')} onChange={(e) => this.setFormValue("division", e.target.value)} key="divsion">
                        <option value="" key="empty">Please select a division (Required)</option>
                        {options}
                    </Form.Control>
                    <Form.Text className="text-muted">
                        What kind of session is this? e.g. Panels, Academic, etc. Some divisions might not be accepting submissions right now; 
                        see the side bar for information about submission dates.
                    </Form.Text>
                </Form.Group>
            );
        } else if (fieldName === "title") {
            return (
                <Form.Group controlId="title" key="title-field">
                    <Form.Label className="sr-only">Title</Form.Label>
                    <Form.Control className={this.getErrorClass('title')} type="text" placeholder="Title (Required)" value={this.getFormValue('title')} onChange={(e) => this.setFormValue('title', e.target.value)} />
                </Form.Group>);
        } else if (fieldName === "progguiddesc" && this.isAcademic(this.getSelectedDivision())) {
            return (
                <Form.Group controlId="progguiddesc" key="progguiddesc-field">
                    <Form.Label className="sr-only">Abstract</Form.Label>
                    <Form.Control as="textarea" rows={3} className={this.getErrorClass('progguiddesc')} type="text" placeholder="Abstract (Required)" value={this.getFormValue('progguiddesc')} onChange={(e) => this.setFormValue('progguiddesc', e.target.value)} />
                    <Form.Text className="text-muted">Max 100 words</Form.Text>
                </Form.Group>
            );
            
        } else if (fieldName === "progguiddesc") {
            return (
                <Form.Group controlId="progguiddesc" key="progguiddesc-field">
                    <Form.Label className="sr-only">Description</Form.Label>
                    <Form.Control as="textarea" rows={3} className={this.getErrorClass('progguiddesc')} type="text" placeholder="Session description (Required)" value={this.getFormValue('progguiddesc')} onChange={(e) => this.setFormValue('progguiddesc', e.target.value)} />
                    <Form.Text className="text-muted">Max 500 characters</Form.Text>
                </Form.Group>
            );
        } else if (fieldName === "pocketprogtext") {
            return (
                <Form.Group controlId="pocketprogtext" key="pocketprogtext-field">
                    <Form.Label className="sr-only">Detailed Proposal (Required)</Form.Label>
                    <Form.Control as="textarea" rows={3} className={this.getErrorClass('pocketprogtext')} type="text" placeholder="Detailed Proposal (Required)" value={this.getFormValue('pocketprogtext')} onChange={(e) => this.setFormValue('pocketprogtext', e.target.value)} />
                    <Form.Text className="text-muted">Max 500 words</Form.Text>
                </Form.Group>
            );
        } else if (fieldName === "track") {
            let tracks = this.getTrackOptions().map((t) => {return (<option value={t.trackid} key={t.trackid}>{t.trackname}</option>) });
            return (
                <Form.Group controlId="track" key="track-field">
                    <Form.Label className="sr-only">Track:</Form.Label>
                    <Form.Control as="select" className={this.getErrorClass('track')} value={this.getFormValue('track')} onChange={(e) => this.setFormValue("track", e.target.value)} key="track">
                        <option value="" key="empty">Please select a track (Required)</option>
                        {tracks}
                    </Form.Control>
                    <Form.Text className="text-muted">Make a best guess about what track this panel should belong to</Form.Text>
                </Form.Group>
            );
        } else if (fieldName === "servicenotes") {
            return (
                <Form.Group controlId="servicenotes" key="servicenotes-field">
                    <Form.Label className="sr-only">Equipment Needed / Alternative Format</Form.Label>
                    <Form.Control as="textarea" rows={3} className={this.getErrorClass('servicenotes')} type="text" placeholder="Additional Equipment Needed / Alternative Format" value={this.getFormValue('servicenotes')} onChange={(e) => this.setFormValue('servicenotes', e.target.value)}/>
                </Form.Group>
            );
        } else if (fieldName === "persppartinfo") {
            return (
                <Form.Group controlId="persppartinfo" key="persppartinfo-field">
                    <Form.Label className="sr-only">Suggest Some Good Participants</Form.Label>
                    <Form.Control as="textarea" rows={3} className={this.getErrorClass('persppartinfo')} type="text" placeholder="Can you suggest some good participants? Or a moderator?" value={this.getFormValue('persppartinfo')} onChange={(e) => this.setFormValue('persppartinfo', e.target.value)}/>
                </Form.Group>
            );
        } else if (fieldName === "notesforprog") {
            return (
                <Form.Group controlId="notesforprog" key="notesforprog-field">
                    <Form.Label className="sr-only">In-Person or Online? (Required)</Form.Label>
                    <Form.Control as="textarea" rows={2} className={this.getErrorClass('notesforprog')} type="text" placeholder="Do you plan to attend in-person, or online? (Required)" value={this.getFormValue('notesforprog')} onChange={(e) => this.setFormValue('notesforprog', e.target.value)}/>
                </Form.Group>
            );
        } else {
            return undefined;
        }
    }

    render() {
        let message = this.state.message ? (<Alert variant={this.state.message.severity}>{this.state.message.text}</Alert>) : undefined;
        const spinner = this.state.loading ? (<Spinner
            as="span"
            animation="border"
            size="sm"
            role="status"
            aria-hidden="true"
        />) : undefined;

        let fieldNames = this.getFieldList(this.getSelectedDivision());
        let fields = fieldNames.map((n) => { return this.createField(n); });

        return (
            <Form onSubmit={(e) =>  this.submitForm(e)}>
                {message}

                <Card>
                    <Card.Header><h2>Submit a Session</h2></Card.Header>
                    <Card.Body>
                        <p>Submissions are open for programming for WisCon 2022.</p>

                        {fields}

                    </Card.Body>
                    <Card.Footer>
                        <Button variant={this.state.submitAllowed ? "primary" : "secondary"} type="submit" disabled={!this.state.submitAllowed}>{spinner} <span>Submit</span></Button>
                    </Card.Footer>
                </Card>
            </Form>
        )
    }

    getTrackOptions() {
        if (this.state.values['division'] && this.getDivisions()) {
            let divisionId = this.state.values['division'];
            return this.getTrackOptionsForDivisionId(divisionId);
        } else {
            return [];
        }
    }

    getTrackOptionsForDivisionId(divisionId) {
        let division = null;
        this.getDivisions().forEach((element) => { if (element.id == divisionId) { division = element; } } );
        return division ? division.tracks : [];
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

        if (formName === 'division') {
            let options = this.getTrackOptionsForDivisionId(formValue);
            if (options.length === 1) {
                newValue["track"] = "" + options[0].trackid;
            } else {
                newValue["track"] = "";
            }
        }

        this.setState((state) => ({
            ...state,
            values: newValue,
            message: null,
            errors: errors
        }));
    }

    selectDefaultTrackForDivision(divisionId) {
        let options = this.getTrackOptionsForDivisionId(divisionId);
        if (options.length === 1) {
            return options[0].trackid;
        } else {
            return undefined;
        }
    }

    validateValue(formName, formValue) {
        if (formName === 'title') {
            return formValue != null && formValue !== '';
        } else if (formName === 'progguiddesc' && this.isAcademic(this.getSelectedDivision())) {
            return formValue != null && formValue != '' && this.wordCount(formValue) <= 100;
        } else if (formName === 'progguiddesc') {
            return formValue != null && formValue != '' && formValue.length <= 500;
        } else if (formName === 'pocketprogtext') {
            return formValue != null && formValue != '' && this.wordCount(formValue) <= 500;
        } else if (formName === 'division') {
            return formValue != null && formValue != '';
        } else if (formName === 'track') {
            return formValue != null && formValue != '';
        } else {
            return true;
        }
    }

    wordCount(text) {
        if (text) {
            return text.trim().split(/\s+/).length;
        } else {
            return 0;
        }
    }

    isValidForm() {
        let formKeys = this.getFieldList(this.getSelectedDivision());
        let errors = this.state.errors || {};
        let valid = true
        formKeys.forEach(element => {
            let v = this.validateValue(element, this.state.values[element]);
            valid &= v;
            errors[element] = !v;
        });

        let message = null;
        if (!valid) {
            message = { severity: "danger", text: "Whoopsie-doodle. It looks like some of this information isn't right."}
        }
        this.setState({
            ...this.state,
            errors: errors,
            message: message
        })
        return valid;
    }

    submitForm(event) {
        event.preventDefault();
        event.stopPropagation();
        const form = event.currentTarget;

        if (this.isValidForm(form)) {
            this.setState({
                ...this.state,
                loading: true
            });
    
            axios.post('/api/brainstorm/submit_session.php', this.getAllFormValues(), {
                headers: {
                    "Authorization": "Bearer " + store.getState().auth.jwt
                }
            })
            .then(res => {
                this.setState({
                    ...this.state,
                    values: this.createInitialValues(),
                    errors: {},
                    loading: false,
                    message: {
                        severity: "success",
                        text: "Thanks for the submission. Suggest another!"
                    }
                });
            })
            .catch(error => {
                this.setState({
                    ...this.state,
                    loading: false,
                    message: {
                        severity: "danger",
                        text: "Sorry. We're had a bit of a technical problem. Try again?"
                    }
                });
            });
        }
    }

    getAllFormValues() {
        let values = this.state.values;
        let names = this.getFieldList(this.getSelectedDivision());
        let result = {};
        names.forEach(n => result[n] = values[n]);
        return result;
    }
}

export default SubmissionForm;