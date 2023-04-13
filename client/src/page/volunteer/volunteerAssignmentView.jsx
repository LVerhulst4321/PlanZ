import React from 'react';
import Spinner from 'react-bootstrap/Spinner';
import Modal from 'react-bootstrap/Modal';
import Form from 'react-bootstrap/Form';
import { connect } from 'react-redux';
import NameDisplay from '../../common/nameDisplay';
import SimpleAlert from '../../common/simpleAlert';
import { formatDay, renderDateRange } from '../../util/dateUtil';
import MemberCard from './memberCard';
import axios from 'axios';
import { redirectToLogin } from '../../common/redirectToLogin';
import { fetchAllShiftAssignments, fetchShifts } from '../../state/volunteerFunctions';

import dayjs from "dayjs";
import utc from 'dayjs/plugin/utc';
import timezone from 'dayjs/plugin/timezone';
import advancedFormat from "dayjs/plugin/advancedFormat"
import customParseFormat from "dayjs/plugin/customParseFormat"
dayjs.extend(utc);
dayjs.extend(timezone);
dayjs.extend(customParseFormat);
dayjs.extend(advancedFormat);

class VolunteerAssignmentView extends React.Component {

    timeout = null;

    constructor(props) {
        super(props);
        this.state = {
            showAddModal: false,
            modalMessage: null,
            selectedShift: null,
            searchTerm: "",
            filter: {
                day: "",
                job: ""
            }
        }
    }

    render() {
        let dayOptions = this.props.days ? this.props.days.map((d) => { return (<option value={d} key={'day-' + d}>{formatDay(d)}</option>)}) : undefined;
        let jobOptions = this.props.jobs ? this.props.jobs.map((j) => { return (<option value={j.id} key={j.id}>{j.name}</option>); }) : undefined;

        return (
            <div>
                <h5 className="mr-3">Volunteer Sign-Ups</h5>

                <fieldset className="border p-3 mb-3 rounded">
                    <legend className="w-auto px-2 text-muted small">Filter</legend>
                    <div className="row">
                        <Form.Group className="col-md-3 col-md-6" controlId="job">
                            <Form.Label className="sr-only">Job</Form.Label>
                            <Form.Control as="select" onChange={(e) => this.filterJob(e.target.value)} value={this.getFilterJob()}>
                                <option value="">All jobs</option>
                                {jobOptions}
                            </Form.Control>
                        </Form.Group>
                        <Form.Group className="col-md-3 col-md-6" controlId="day">
                            <Form.Label className="sr-only">Day</Form.Label>
                            <Form.Control as="select" onChange={(e) => this.filterDay(e.target.value)} value={this.getFilterDay()}>
                                <option value="">All days</option>
                                {dayOptions}
                            </Form.Control>
                        </Form.Group>
                    </div>
                </fieldset>

                <p>The following people been assigned to shifts.</p>

                <SimpleAlert message={this.props.message} />

                {this.renderMain()}
            </div>
        );
    }

    renderMain() {
        if (this.props.assignments.loading) {
            return (
                <div className="text-center">
                    <Spinner animation="border" />
                </div>
            );
        } else {
            let rows = this.getFilteredAssignments().map(a => {

                let volunteers = a.volunteers?.length ? a.volunteers?.map((v,i) => {
                    return (<tr className="visible-on-hover" key={'assign-' + a.id + "-" + i}>
                        <td><NameDisplay name={v.name} /></td>
                        <td className="text-right"><button className="btn p-0 border-0" onClick={() => this.deleteVolunteerAssignment(a.id, v.badgeId)}><i className="bi bi-trash text-danger"></i></button></td>
                    </tr>)
                })
                : (<tr>
                    <td colSpan={2} className="text-info">No sign-ups</td>
                </tr>);
                return (<tbody key={'assignment-' + a.id}>
                    <tr>
                        <th><b>{a.job.name}:</b> {renderDateRange(a.fromTime, null, this.props.timezone)}</th>
                        <th className="text-right"><button className="btn btn-outline-primary btn-sm" onClick={() => this.showAddModal(a.id)}>Add</button></th>
                    </tr>
                    {volunteers}
                </tbody>);
            });

            return (<>
                <table className="table table-striped">
                    <thead>
                        <tr>
                            <th colSpan={2}>Name</th>
                        </tr>
                    </thead>
                    {rows}
                </table>
                {this.renderModal()}
                </>
            );
        }
    }

    deleteVolunteerAssignment(shiftId, badgeId) {
        axios.delete('/api/volunteer/volunteer_shift_assignment.php', {
            data: { shiftId: shiftId, badgeId: badgeId }
        })
        .then(res => {
            fetchAllShiftAssignments();
            fetchShifts();
        })
        .catch(error => {
            if (error.response && error.response.status === 401) {
                redirectToLogin();
            }
        });
    }

    showAddModal(shiftId) {
        this.setState((state) => ({...state, showAddModal: true, selectedShift: shiftId }));
    }

    renderModal() {
        return (<Modal show={this.state.showAddModal} size="lg" onHide={() => this.closeAddModal()}>
            <Modal.Header closeButton>
                <Modal.Title>Assign Volunteer</Modal.Title>
            </Modal.Header>
            <Modal.Body>
                <SimpleAlert message={this.state.modalMessage} />
                <p>Search for <b>registered</b> potential volunteers:</p>
                <div className="row">
                    <div className="form-group col-md-6">
                        <label htmlFor="candidate-search" className="sr-only">Search</label>
                        <input type="text" id="candidate-search" className="form-control"
                            value={this.state.searchTerm} autoComplete="off" name="q" placeholder="Search..."
                            onChange={(e) => this.executeQuery(e.target.value)} />
                    </div>
                </div>
                {this.state.candidates?.map(c => (<MemberCard candidate={c} shiftId={this.state.selectedShift}
                    closeModal={() => this.closeAddModal()} key={'other-' + c.badgeId} />))}
                {this.state.candidates?.length ? null : (<p className="my-3 text-info">This list is empty.</p>)}
            </Modal.Body>
        </Modal>);
    }

    executeQuery(queryString) {
        if (this.timeout) {
            clearTimeout(this.timeout);
            this.timeout = undefined;
        }
        this.setState(state => ({...state, searchTerm: queryString}));
        if (queryString) {
            this.timeout = setTimeout(() => {
                this.fetchPotentialVolunteers(this.state.selectedShift, queryString);
                this.timeout = undefined;
            }, 1000);
        }
    }

    closeAddModal() {
        this.setState((state) => ({...state, showAddModal: false, searchTerm: "", modalMessage: null, candidates: null}));
    }

    fetchPotentialVolunteers(shiftId, queryString) {
        axios.get('/api/volunteer/find_potential_volunteers.php?shiftId=' + encodeURIComponent(shiftId) + "&q=" + encodeURIComponent(queryString))
            .then(res => {
                this.setState(state => ({...state, candidates: res.data?.candidates, modalMessage: null }))
            })
            .catch(error => {
                if (error.response && error.response.status === 401) {
                    redirectToLogin();
                } else {
                    let message = "The list of candidates could not be downloaded."
                    this.setState(state => ({...state, candidates: [], modalMessage: message }))
                }
            }
        );
    }

    getFilteredAssignments() {
        return this.props.assignments?.list.filter(a => this.matchesFilter(a));
    }

    // NOTE: the comparisons in this function don't support '==='
    matchesFilter(shift) {
        let matches = true;
        let filterDay = this.getFilterDay();
        let filterJob = this.getFilterJob();

        if (filterDay === "") {
            // skip it
        } else if (!(dayjs(shift.fromTime).format('YYYY-MM-DD')?.toString() === filterDay?.toString()) &&
            !(dayjs(shift.toTime).format('YYYY-MM-DD')?.toString() === filterDay?.toString())) {

            matches = false;
        }

        if (filterJob === "" || !matches) {
            // skip it
        } else if (filterJob?.toString() !== shift.job?.id?.toString()) {
            matches = false;
        }

        return matches;
    }

    filterDay(day) {
        this.setState((state) => ({...state, filter: {...state.filter, day: day }}));
    }

    filterJob(job) {
        this.setState((state) => ({...state, filter: {...state.filter, job: job }}));
    }

    getFilterDay() {
        return this.state.filter && this.state.filter.day != null ? this.state.filter.day : "";
    }

    getFilterJob() {
        return this.state.filter && this.state.filter.job != null ? this.state.filter.job : "";
    }
}

function mapStateToProps(state) {
    return {
        message: state.volunteering.message,
        assignments: state.volunteering.allAssignments,
        timezone: state.volunteering.shifts?.context?.timezone,
        jobs: state.volunteering.jobs?.list,
        days: state.volunteering.shifts?.context?.days,
    };
}
export default connect(mapStateToProps)(VolunteerAssignmentView);