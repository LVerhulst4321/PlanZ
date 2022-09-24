import React from 'react';
import { connect } from 'react-redux';

const SidebarText = (props) => {
    return (
        <div dangerouslySetInnerHTML={{ __html: props.sidebar }}></div>
    );
}

function mapStateToProps(state) {
    return { sidebar: (state.brainstorm != null && state.brainstorm.customText != null) ? state.brainstorm.customText.sidebar : ""};
}

export default connect(mapStateToProps)(SidebarText);