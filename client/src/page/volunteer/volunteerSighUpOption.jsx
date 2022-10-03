import React from 'react';
import Button from 'react-bootstrap/Button';

import LoadingButton from "../../common/loadingButton";
import { renderDateRange } from '../../util/dateUtil';

class VolunteerSignUpOption extends React.Component {

    constructor(props) {
        super(props);
        this.state = {
            loading: false,
            showDetails: false
        }
    }

    render() {
        let shift = this.props.shift;
        let emphasis = this.highNeed(shift);
        return (<div className={'card mb-3 ' + (emphasis ? 'border-primary' : '')} >
            <div className="card-body">
                <div className="row">
                    <div className="col-md-12"><b>{shift.job.name}:</b> {renderDateRange(shift.fromTime, shift.toTime, this.props.timezone)}</div>                        
                </div>
                <div className="row">
                    <div className="col-md-6"><b>Needs:</b> {shift.minPeople}&ndash;{shift.maxPeople} volunteers <span>(has {shift.currentSignupCount})</span></div>
                    <div className="col-md-6"><b>Location:</b> {shift.location} </div>
                </div>
                {this.state.showDetails ? (<div className="row">
                    <div className="col-md-12 small">{shift.job.description}</div>
                </div>) : undefined}
                <div className="row align-items-end">
                    <div className="col-md-6">
                        <Button variant="outline-secondary" className="btn-sm mr-2" onClick={() => this.setState((state) => ({...state, showDetails: !state.showDetails }))}>
                            {this.state.showDetails ? 'hide details' : 'details'}
                        </Button>
                    </div>
                    <div className="col-md-6 text-right">
                        <LoadingButton onClick={() => {
                            this.setState((state) => ({...state, loading: true }));
                            this.props.onClick(shift);
                        }} enabled={true} loading={this.state.loading}>Add</LoadingButton>
                    </div>
                </div>
            </div>
        </div>);
    }

    highNeed(shift) {
        return shift.currentSignupCount < shift.minPeople;
    }
}

export default VolunteerSignUpOption;