import React, { Component } from 'react';
import {connect} from 'react-redux';

import Alert from 'react-bootstrap/Alert';
import Button from 'react-bootstrap/Button';
import Form from 'react-bootstrap/Form';
import Modal from 'react-bootstrap/Modal';
import { extractAndDispatchJwt, hideLoginModal, logout } from '../state/authActions';
import store from '../state/store';
import axios from 'axios';

class LoginModal extends Component {

    constructor(props) {
        super(props);
        this.state = {
            login: {}
        };
    }

    render() {

        let message = (this.state.login.message) ? (<Alert variant='danger'>{this.state.login.message}</Alert>) : undefined;

        return (
            <Modal show={this.props.showModal}  onHide={() => this.handleClose()} key="page-header-login-dialog">
                <Form>
                    <Modal.Header closeButton>
                    <Modal.Title>Login</Modal.Title>
                    </Modal.Header>
                    <Modal.Body>
                        {message}
                        <p>Please log in to submit session ideas.</p>
                        <Form.Group className="mb-3" controlId="formEmail">
                            <Form.Label className="sr-only">Email</Form.Label>
                            <Form.Control type="email" placeholder="Enter email" value={this.state.userid} onChange={(e) => this.setUserid(e.target.value)}/>
                        </Form.Group>
                        <Form.Group className="mb-3" controlId="formPasswod">
                            <Form.Label className="sr-only">Password</Form.Label>
                            <Form.Control type="password" placeholder="Password"  value={this.state.password} onChange={(e) => this.setPassword(e.target.value)}/>
                        </Form.Group>
                    </Modal.Body>
                    <Modal.Footer>
                        <a href="/ForgotPassword.php" className="btn btn-link" target="_blank" rel="noreferrer">Reset password</a>
                        <Button type="submit" variant="primary" onClick={(e) => {e.preventDefault(); this.processLogin();}} disabled={!this.state.login.loginEnabled}>
                            Login
                        </Button>
                    </Modal.Footer>
                </Form>
            </Modal>);
    }

    handleClose() {
        store.dispatch(hideLoginModal());
    }

    processLogin() {
        axios.post('/api/login.php', {
            userid: this.state.login.userid,
            password: this.state.login.password
        })
        .then(res => {
            extractAndDispatchJwt(res);
            this.handleClose();
        })
        .catch(error => {
            console.log(error);
            let message = "There was a technical problem trying to log you in. Try again later."
            if (error.response && error.response.status === 401) {
                message = "There was a problem with your userid and/or password."
            }
            this.setState((state) => ({
                ...state,
                login: {
                    ...state.login,
                    message: message
                }
            }))
        });
    }

    setUserid(userid) {
        let state = this.state;
        let enabled = state.login.loginEnabled;
        if (userid && this.state.login.password) {
            enabled = true;
        } else {
            enabled = false;
        }
        this.setState({
            ...state,
            login: {
                ...state.login,
                userid: userid,
                loginEnabled: enabled,
                message: undefined
            }
        });
    }

    setPassword(value) {
        let state = this.state;
        let enabled = state.login.loginEnabled;
        if (this.state.login.userid && value) {
            enabled = true;
        } else {
            enabled = false;
        }
        this.setState({
            ...state,
            login: {
                ...state.login,
                password: value,
                loginEnabled: enabled,
                message: undefined
            }
        });
    }
}

function mapStateToProps(state) {
    return { showModal: state.auth.showModal };
}

export default connect(mapStateToProps)(LoginModal);
