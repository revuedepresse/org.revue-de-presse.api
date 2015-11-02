
function getStringFormatter() {
    var StringFormatter = function () {};

    /**
     * See http://stackoverflow.com/a/11172083/282073 to get local date
     *
     * @param date
     * @param preventSlicing
     * @returns {string}
     */
    StringFormatter.prototype.formatDate = function (date, preventSlicing) {
        var local = new Date(date);
        var sourceDate = new Date(date);
        local.setMinutes(sourceDate.getMinutes() - sourceDate.getTimezoneOffset());

        if (preventSlicing) {
            return local.toJSON();
        } else {
            return local.toJSON().slice(0, 10);
        }
    };

    return new StringFormatter();
}

if (window) {
    window.stringFormatter = getStringFormatter();
}
