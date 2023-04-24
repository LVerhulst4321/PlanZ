import React from 'react';

import dayjs from "dayjs";
import utc from 'dayjs/plugin/utc';
import timezone from 'dayjs/plugin/timezone';
import advancedFormat from "dayjs/plugin/advancedFormat"
import customParseFormat from "dayjs/plugin/customParseFormat"
dayjs.extend(utc);
dayjs.extend(timezone);
dayjs.extend(customParseFormat);
dayjs.extend(advancedFormat);

export function renderDateRange(fromTime, toTime, timezone) {

    let from = dayjs(fromTime);
    let fromLocal = from;
    let to = toTime == null ? null : dayjs(toTime);
    let toLocal = to;
    if (timezone) {
        from = dayjs(fromTime).tz(timezone);
        to = toTime == null ? null : dayjs(toTime).tz(timezone);
    }
    let fromString = from.format("ddd h:mm a");
    let fromLocalString = fromLocal.format("ddd h:mm a z");
    if (to != null) {
        let toString = to.format("ddd h:mm a");
        let toLocalString = toLocal.format("ddd h:mm a z");
        let zone = to.format("z");
        if (from.format("ddd") === to.format("ddd")) {
            toString = to.format("h:mm a");
        }
        return (<span><time title={fromLocalString}>{fromString}</time>&ndash;<time title={toLocalString}>{toString} <small>{zone}</small></time></span>);
    } else {
        return (<span title={fromLocalString}>{fromString}</span>);
    }
}

export function formatDay(day) {
    return dayjs(day).format('dddd (MMM D)');
}