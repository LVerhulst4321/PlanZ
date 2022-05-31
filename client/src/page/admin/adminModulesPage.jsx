import React from 'react';
import { Form } from 'react-bootstrap';
import { connect } from 'react-redux';
import { fetchModules } from '../../state/moduleFunctions';

class AdminModulesPage extends React.Component {

    componentDidMount() {
        if (this.props.modules.loading) {
            fetchModules();
        }
    }

    render() {
        return (            
            <div className="card mb-3">
                <div className="card-header">
                    <h2>Administer Modules</h2>
                </div>
                <div className="card-body">
                    <p>This page allows administrators to decide which modules are available for their instance of PlanZ.</p>
                    {this.renderModulesTable()}
                </div>
            </div>
        )
    }

    renderModulesTable() {
        if (this.props.modules && this.props.modules.list) {
            const rows = this.props.modules.list.map((m) => { 
                return (<tr key={'module-' + m.id}>
                        <td className="align-middle">{m.id}</td>
                        <td className="align-middle">
                            <Form.Control as="select" value={this.getEnabledValue(m)} onChange={(e) => this.setEnabledValue(e.target.value, value)}>
                                <option value={'false'}>Not enabled</option>
                                <option value={'true'}>Enabled</option>
                            </Form.Control>
                        </td>
                        <td className="align-middle">{m.name}</td>
                        <td className="align-middle">{m.description}</td>
                    </tr>); 
            });

            return (<table className="table table-sm table-striped">
                <thead>
                    <tr>
                        <th>Id</th>
                        <th>Status</th>
                        <th>Name</th>
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

    getEnabledValue(module) {
        return "true";
    }

    setEnabledValue(moddule) {

    }
}

function mapStateToProps(state) {
    return { 
        modules: state.modules || {}
    };
}
export default connect(mapStateToProps)(AdminModulesPage);