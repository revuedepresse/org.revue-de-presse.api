
function getQueryParams(queryString) {
    var QueryParams = function (queryString) {
        this.queryString = queryString;
    };

    /**
     * See also http://stackoverflow.com/a/1099670/282073
     *
     * @returns {Object}
     */
    QueryParams.prototype.fromQueryString = function() {
        var qs = this.queryString.split('+').join(' ');

        var params = {},
            tokens,
            re = /[?&]?([^=]+)=([^&]*)/g;

        tokens = re.exec(qs);
        while (tokens) {
            params[decodeURIComponent(tokens[1])] = decodeURIComponent(tokens[2]);
            tokens = re.exec(qs);
        }

        return params;
    };

    var queryParams = new QueryParams(queryString);

    return queryParams.fromQueryString();
}

if (window && window.location) {
    window.queryParams = getQueryParams(window.location.search);
}

