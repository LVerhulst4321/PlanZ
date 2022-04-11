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
    let to = toTime == null ? null : dayjs(toTime);
    if (timezone) {
        from = dayjs(fromTime).tz(timezone);
        to = toTime == null ? null : dayjs(toTime).tz(timezone);
    }
    let fromString = from.format("ddd h:mm a");
    if (to != null) {
        let toString = to.format("ddd h:mm a z");
        if (from.format("ddd") === to.format("ddd")) {
            toString = to.format("h:mm a z");
        }
        return (<span>{fromString}&ndash;{toString}</span>);
    } else {
        return (<span>{fromString}</span>);
    }
}

export function formatDay(day) {
    return dayjs(day).format('dddd (MMM D)');
}