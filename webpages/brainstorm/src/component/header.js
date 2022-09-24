import React, { Component } from 'react';
import {connect} from 'react-redux';

import Alert from 'react-bootstrap/Alert';
import Nav from 'react-bootstrap/Nav';
import Navbar from 'react-bootstrap/Navbar';
import NavDropdown from 'react-bootstrap/NavDropdown';

import store from '../state/store';
import { logout, showLoginModal } from '../state/authActions';

class PageHeader extends Component {

    render() {
        let loginMenu = this.isAuthenticated() 
            ? (<NavDropdown title={this.getName()} id="admin-nav-dropdown">
                <NavDropdown.Item onClick={() => this.logout()}>Logout</NavDropdown.Item>
            </NavDropdown>) 
            : (<Nav.Link onClick={() => this.presentModal()}>Login</Nav.Link>);
        let loginMessage = (!this.isAuthenticated()) ? (<Alert variant="warning">Please <a className="alert-link" href="https://program.wiscon.net" onClick={(e) => { e.preventDefault(); this.presentModal();} }>log in</a> to submit session ideas.</Alert>) : undefined;
        let website = (this.props.links && this.props.links.website) 
            ? (<Nav.Link href={this.props.links.website.url} target="_blank" rel="noreferrer">{this.props.links.website.name}</Nav.Link>)
            : undefined;
        return [
            <header className="mb-3" key="page-header-main">
                <img className="w-100" src="/HeaderImage.php" alt="page header" />
                <Navbar bg="dark" expand="lg" className="navbar-dark navbar-expand-md justify-content-between">
                    <Nav className="navbar-expand-md navbar-dark bg-dark ">
                        <Nav.Link href="/welcome.php" rel="noreferrer">Home</Nav.Link>
                        {website}
                    </Nav>
                    <Nav className="navbar-expand-md navbar-dark bg-dark ml-auto">
                        {loginMenu}
                    </Nav>
                </Navbar>
            </header>,
            <div key="login-message">
                {loginMessage}
            </div>
        ];
    }

    presentModal() {
        store.dispatch(showLoginModal());
    }

    getName() {
        if (this.isAuthenticated()) {
            let jwt = this.props.jwt;
            let parts = jwt.split('.');
            if (parts.length === 3) {
                let payload = JSON.parse(atob(parts[1]));
                return payload['name'] || "Your Name Here";
            } else {
                return "Your Name Here";
            }
        } else {
            return undefined;
        }
    }

    isAuthenticated() {
        return this.props && this.props.jwt;
    }

    logout() {
        store.dispatch(logout());
    }
}

function mapStateToProps(state) {
    return { jwt: state.auth.jwt, links: state.options.con ? state.options.con.links : null };
}

export default connect(mapStateToProps)(PageHeader);