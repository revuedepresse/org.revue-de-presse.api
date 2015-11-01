
function getQueryParams() {
    /**
     * See also http://stackoverflow.com/a/1099670/282073
     *
     * @param qs
     * @returns {{}}
     */
    function getQueryParamsFromQueryString(qs) {
        qs = qs.split('+').join(' ');

        var params = {},
            tokens,
            re = /[?&]?([^=]+)=([^&]*)/g;

        while (tokens = re.exec(qs)) {
            params[decodeURIComponent(tokens[1])] = decodeURIComponent(tokens[2]);
        }

        return params;
    }

    if (window && window.location !== undefined) {
        return getQueryParamsFromQueryString(window.location.search);
    } else {
        throw Error('Window location is required to get query params.');
    }
}


