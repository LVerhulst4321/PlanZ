import React, { Component } from 'react';
import {connect} from 'react-redux';

import dayjs from "dayjs";
import utc from 'dayjs/plugin/utc';
import timezone from 'dayjs/plugin/timezone';
import advancedFormat from "dayjs/plugin/advancedFormat"
dayjs.extend(utc);
dayjs.extend(timezone);
dayjs.extend(advancedFormat);

class DateInfo extends Component {

    render() {
        let items = this.props.divisions.map((d) => {return this.formatDivision(d)});

        return (
            <div className="card mb-3">
                <div className="card-body">
                    <h5>Submission Dates</h5>
                    {items}
                </div> 
            </div>
        );
    }
    
    formatDivision(division) {
        if (division.to_time) {
            let toDate = dayjs(division.to_time);
            let now = dayjs();
            if (toDate.diff(now) < 0) {
                return (
                    <div className="mt-2" key={'division' + division.id}><br />
                        {division.name}: <b><span className="text-danger">CLOSED</span></b>
                    </div>
                );
            } else {

                let to = dayjs(division.to_time).format("MMM Do, YYYY [at] h:mm a z");
                let tz = dayjs.tz.guess();
                return (
                    <div className="mt-2" key={'division' + division.id}>
                        {division.name} <span className="text-muted">open until:</span><br />
                        <b><time dateTime={division.to_time}>{to}</time>.</b>
                    </div>
                );
            }
        } else {
            return undefined;
        }
    }
}

function mapStateToProps(state) {
    return { divisions: state.options.divisions || [] };
}

export default connect(mapStateToProps)(DateInfo);