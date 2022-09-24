import React from 'react';
import { connect } from 'react-redux';

const SidebarText = (props) => {
    return (
        <div dangerouslySetInnerHTML={{ __html: props.sidebar }}></div>
    );
}

function mapStateToProps(state) {
    return { sidebar: (state.options != null && state.options.customText != null) ? state.options.customText.sidebar : ""};
}

export default connect(mapStateToProps)(SidebarText);