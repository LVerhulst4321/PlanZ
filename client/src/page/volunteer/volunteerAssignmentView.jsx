import React from 'react';
import Spinner from 'react-bootstrap/Spinner';
import Modal from 'react-bootstrap/Modal';
import { connect } from 'react-redux';
import NameDisplay from '../../common/nameDisplay';
import SimpleAlert from '../../common/simpleAlert';
import { renderDateRange } from '../../util/dateUtil';
import MemberCard from './memberCard';
import axios from 'axios';
import { redirectToLogin } from '../../common/redirectToLogin';
import { fetchAllShiftAssignments, fetchShifts } from '../../state/volunteerFunctions';


class VolunteerAssignmentView extends React.Component {

    timeout = null;

    constructor(props) {
        super(props);
        this.state = {
            showAddModal: false,
            modalMessage: null,
            selectedShift: null,
            searchTerm: ""
        }
    }

    render() {
        return (
            <div>
                <h5 className="mr-3">Volunteer Sign-Ups</h5>
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
            let rows = this.props.assignments.list.map(a => {

                let volunteers = a.volunteers?.length ? a.volunteers?.map((v,i) => {
                    return (<tr className="visible-on-hover" key={'assign-' + a.id + "-" + i}>
                        <td><NameDisplay name={v.name} /></td>
                        <td className="text-right"><button className="btn p-0" onClick={() => this.deleteVolunteerAssignment(a.id, v.badgeId)}><i className="bi bi-trash text-danger"></i></button></td>
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
                {this.state.cadidates?.map(c => (<MemberCard candidate={c} shiftId={this.state.selectedShift}
                    closeModal={() => this.closeAddModal()} key={'other-' + c.badgeId} />))}
                {this.state.cadidates?.length ? null : (<p className="my-3 text-info">This list is empty.</p>)}
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
                this.setState(state => ({...state, cadidates: res.data?.candidates, modalMessage: null }))
            })
            .catch(error => {
                if (error.response && error.response.status === 401) {
                    redirectToLogin();
                } else {
                    let message = "The list of candidates could not be downloaded."
                    this.setState(state => ({...state, cadidates: [], modalMessage: message }))
                }
            }
        );
    }
}

function mapStateToProps(state) {
    return {
        message: state.volunteering.message,
        assignments: state.volunteering.allAssignments,
        timezone: state.volunteering.shifts?.context?.timezone
    };
}
export default connect(mapStateToProps)(VolunteerAssignmentView);