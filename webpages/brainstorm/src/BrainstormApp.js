import React from 'react';

import Container from 'react-bootstrap/Container';

import DateInfo from './component/dateInfo';
import Footer from './component/footer';
import MainBody from './component/mainBody';
import PageHeader from './component/header';

import './scss/brainstorm.scss';
import LoginModal from './component/loginModal';
import SidebarText from './component/sidebarText';

const BrainstormApp = () => (
    <Container>
        <PageHeader />
        <div className="row">
            <section className="col-md-9">
                <MainBody />
            </section>
            <section className="col-md-3">
                <DateInfo />
                <SidebarText />
            </section>
        </div>
        <LoginModal />
        <Footer />
    </Container>
);

export default BrainstormApp;