import React from 'react';
import StaffVolunteerPage from './volunteer/staffVolunteerPage';

/**
 * Implementing this as a sort-of pauper's version of a Router. We're starting with an 
 * assumption (that might, or might not be true) that when the page loads, the React
 * app will do one thing (based on the URL), and that "leaving" the page will result
 * in a completely different instance of the app doing a different thing. Even if all
 * the code is the same, different URLs invoke different abilities.
 * 
 * We can revisit this pattern later.
 */
class CompositePage extends React.Component {

    render() {
        let url = new URL(window.location.href);
        if (url.pathname === '/StaffVolunteerPage.php') {
            return (<StaffVolunteerPage />);
        } else {
            return (<h4>Unknown Page</h4>);
        }
    }
}

export default CompositePage;