import React from 'react';

class StaffVolunteerPage extends React.Component {

    render() {
        return (
            <div className="row">
                <div className="col-md-8">
                    <div className="card">
                        <div className="card-header">
                            <h4>Schedule Volunteer Shifts</h4>
                        </div>
                        <div className="card-body">

                        </div>
                    </div>
                </div>
                <div className="col-md-4">
                    <h5>Volunteer Jobs</h5>

                    <div className="row row-cols-1 row-cols-md-2 mt-3">
                        <div className="col mb-4">
                            <div className="card">
                                <div className="card-header">
                                    <b>Setup / Teardown</b>
                                </div>
                                <div className="card-body">
                                    Part of making WisCon happen involves setting up our physical supplies on Thursday afternoon, 
                                        and then packing up on Monday. If you are available at one of those times and able to pack or unpack boxes, 
                                        load boxes onto carts, or move a loaded cart between floors, your help is greatly appreciated!
                                </div>
                            </div>
                        </div>
                        <div className="col">
                            <div className="card mb-4">
                                <div className="card-header">
                                    <b>Art Show</b>
                                </div>
                                <div className="card-body">
                                    Art Show volunteers monitor the space for the Art Show, answer questions, and manage sales during the con. 
                                        This role is a good fit if you are comfortable handling money, working in a public-facing sales role, or 
                                        if you prefer a volunteer role that involves remaining seated in a relatively quiet room.
                                </div>
                            </div>
                        </div>
                        <div className="col">
                            <div className="card mb-4">
                                <div className="card-header">
                                    <b>Consuite</b>
                                </div>
                                <div className="card-body">
                                    This year the Consuite will not be serving hot meals, but volunteers are still needed to ensure that our grab &amp; 
                                    go items are stocked, to refill coffee and tea as needed, and otherwise to keep the Consuite spaces clean and safe. 
                                    This role requires standing and moving items, as well as safe food handling (though certification will not be required). 
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        )
    }
}

export default StaffVolunteerPage;