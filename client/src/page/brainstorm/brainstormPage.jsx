import React from 'react';
import DateInfo from './dateInfo';
import MainBody from './mainBody';
import SidebarText from './sidebarText';

const BrainstormPage = () => {
    return (<div className="card">
        <div className="card-body">
            <div className="row">
                <section className="col-md-9">
                    <MainBody />
                </section>
                <section className="col-md-3">
                    <DateInfo />
                    <SidebarText />
                </section>
            </div>
        </div>
    </div>);
}

export default BrainstormPage;