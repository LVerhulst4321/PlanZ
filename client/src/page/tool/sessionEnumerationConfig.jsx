import React, { useEffect, useState } from 'react';
import axios from 'axios';
import LoadingButton from '../../common/loadingButton';
import { redirectToLogin } from '../../common/redirectToLogin';
import SimpleAlert from '../../common/simpleAlert';

const SessionEnumerationConfigPage = () => {

    let [buttonVariant, setButtonVariant] = useState("primary");
    let [loading, setLoading] = useState(false);
    let [message, setMessage] = useState(null);

    useEffect(() => {
        axios.get('/api/tool/session_enumerator.php')
        .then(res => {
            if (res.data.count > 0) {
                setMessage({
                    severity: "warning",
                    text: "Sessions have already been assigned session numbers. Clicking 'Proceed' will overwrite those numbers."
                });
                setButtonVariant('danger');
            }
        })
        .catch(error => {
            if (error.response && error.response.status === 401) {
                redirectToLogin();
            } else {
                setMessage({
                    severity: "danger",
                    text: "We've hit a bit of a technical snag trying to find out if session numbers have already been assigned."
                });
            }
        });
    }, []);

    const assignNumbers = () => {
        setLoading(true);
        axios.post('/api/tool/session_enumerator.php')
            .then(res => {
                setLoading(false);
                setMessage({
                        severity: "success",
                        text: "Ok. Session numbers have been assigned."
                    });
            })
            .catch(error => {
                console.log(error);
                if (error.response && error.response.status === 401) {
                    redirectToLogin();
                } else {
                    setLoading(false);
                    setMessage({
                            severity: "danger",
                            text: "Sorry. We've had a bit of a technical problem. Try again?"
                        });
                }
            });
    }

    return (<>
        <SimpleAlert message={message} />
        <div className="card mt-3">
            <div className="card-header">
                <h2>Session Enumeration</h2>
            </div>
            <div className="card-body">
                <p>This tool assigns simple numbers to each of the sessions on the current schedule. The numbers should be assigned
                based on start time, and room. The intention is that if you look at the grid view of the session, the numbers appear
                to start at '1' and increment as you read left-to-right, top-to-bottom.</p>
            </div>
            <div className="card-footer text-right">
                <LoadingButton variant={buttonVariant} loading={loading} onClick={() => assignNumbers() } enabled={true}>Proceed</LoadingButton>
            </div>
        </div>
        </>);
}

export default SessionEnumerationConfigPage;