
const formatDate = (dateParam, localeParam = null) => {
    const date = new Date(dateParam);
    const localeString = localeParam === null ? navigator.language : localeParam;
    const timeString = date.toLocaleTimeString(localeString, {
        year: "numeric",
        month: "numeric",
        day: "numeric",
        hour: "numeric",
        minute: "numeric",
        second: "numeric"
    });

    let lengthDate = timeString.indexOf(',');
    let ymd = timeString.substring(0, lengthDate);
    let hms = timeString.substring(lengthDate + 2);
    return ymd + ' ' + hms;
};

export default formatDate;