import axios from 'axios';
import React from 'react';
import { Alert, Form } from 'react-bootstrap';
import { connect } from 'react-redux';
import LoadingButton from '../../common/loadingButton';
import { redirectToLogin } from '../../common/redirectToLogin';
import SimpleAlert from '../../common/simpleAlert';
import { fetchModules } from '../../state/moduleFunctions';

class AdminModulesPage extends React.Component {

    constructor(props) {
        super(props);
        this.state = {
            modules: [],
            loading: false,
            message: null
        };
    }

    componentDidMount() {
        if (this.props.modules.loading) {
            fetchModules();
        }
    }

    render() {
        return (
            <div>
                <SimpleAlert message={this.state.message} />
                <Alert variant='info'>Modules are optional features of the PlanZ application. Not all cons will want to use these features,
                    so they may be turned off and on for your instance of PlanZ.</Alert>
                <div className="card mb-3">
                    <div className="card-header">
                        <h2>Administer Modules</h2>
                    </div>
                    <div className="card-body">
                        <p>This page allows administrators to decide which modules are available for their instance of PlanZ.</p>
                        {this.renderModulesTable()}
                    </div>
                    <div className="card-footer text-right">
                        <LoadingButton variant="primary" type="button" onClick={() => this.saveChanges()}
                            enabled={this.state.modules.length !== 0}
                            loading={this.state.loading}>Save</LoadingButton>
                    </div>
                </div>
            </div>
        )
    }

    renderModulesTable() {
        if (this.props.modules && this.props.modules.list) {
            const rows = this.props.modules.list.map((m) => {
                let highlight = this.state.modules.indexOf(m.id) >= 0;
                return (<tr key={'module-' + m.id} className={highlight ? 'highlight' : ''}>
                        <td className="align-middle">{m.name}</td>
                        <td className="align-middle">
                            <Form.Control as="select" value={this.getEnabledValue(m)} onChange={(e) => this.setEnabledValue(e.target.value, m)}>
                                <option value={'false'}>Not enabled</option>
                                <option value={'true'}>Enabled</option>
                            </Form.Control>
                        </td>
                        <td className="align-middle">{m.description}</td>
                    </tr>);
            });

            return (<table className="table table-striped">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Status</th>
                        <th>Description</th>
                    </tr>
                </thead>
                <tbody>
                    {rows}
                </tbody>
            </table>)
        } else {
            return null;
        }
    }

    getEnabledValue(planzModule) {
        let result = false;
        this.props.modules.list.forEach((m) => { if (m.id === planzModule.id) result = m.isEnabled });
        return (this.state.modules.indexOf(planzModule.id) >= 0) ? !result : result;
    }

    setEnabledValue(value, planzModule) {
        if (this.state.modules.indexOf(planzModule.id) >= 0) {
            this.setState((state) => {
                let temp = [ ...state.modules ];
                temp.splice(temp.indexOf(planzModule.id), 1);
                return {...state, modules: temp, message: null };
            });
        } else {
            this.setState((state) => ({...state, message: null, modules: [...state.modules, planzModule.id ]}));
        }

    }

    saveChanges() {
        let data = {};
        this.props.modules.list.forEach((m) => {
            if (this.state.modules.indexOf(m.id) >= 0) {
                data[m.packageName] = !m.isEnabled;
            }
        });

        this.setState((state) => ({
            ...state,
            loading: true,
            message: null
        }));

        axios.post('/api/admin/modules.php', data)
            .then(res => {
                this.setState({
                    ...this.state,
                    loading: false,
                    message: {
                        severity: "success",
                        text: "Ok. Your changes have been saved."
                    },
                    modules: []
                });
                fetchModules();
            })
            .catch(error => {
                console.log(error);
                if (error.response && error.response.status === 401) {
                    redirectToLogin();
                } else {
                    this.setState({
                        ...this.state,
                        loading: null,
                        message: {
                            severity: "danger",
                            text: "Sorry. We've had a bit of a technical problem. Try again?"
                        }
                    });
                }
            });
    }
}

function mapStateToProps(state) {
    return {
        modules: state.modules || []
    };
}
export default connect(mapStateToProps)(AdminModulesPage);