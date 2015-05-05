
(function (keywords, since, until) {
    since = new Date(since);
    until = new Date(until);

    /**
     * See http://stackoverflow.com/a/11172083/282073 to get local date
     *
     * @param date
     * @returns {string}
     */
    function formatDate(date) {
        var local = new Date(date);
        local.setMinutes(date.getMinutes() - date.getTimezoneOffset());

        return local.toJSON().slice(0, 10);
    }

    var endpoint = '/api/twitter/aggregate/' + keywords.join(',') + '/' + formatDate(since) + '/' + formatDate(until);

    d3.json(endpoint)
    .header("Authorization", "Bearer " + token)
    .get(function(error, data) {
        var index;
        var timeSeries = data['time_series'];

        for (index = 0 ; index < keywords.length ; index++) {
            timeSeries[index] = MG.convert.date(timeSeries[index], 'date');
        }

        MG.data_graphic({
            title: 'Mentions in personal network',
            description: 'From ' + since + ' to ' + until,
            data: timeSeries,
            width: 1500,
            height: 800,
            right: 150,
            target: document.getElementById('time-series-mentions'),
            legend: keywords,
            x_accessor: 'date',
            y_accessor: 'mentions'
        });

        var aggregations = data['screen_name_aggregations'];

        var header = $('<tr />');
        header.append($('<th />', {text: 'Date', "class": 'col-md-3'}));

        $('#aggregated-mentions thead').append(header);

        var row;
        var date;
        var screenName;
        var keywordIndex;

        var tableBody = $('#aggregated-mentions tbody');

        for (date in aggregations) {
            if (aggregations.hasOwnProperty(date)) {
                row = $('<tr />');
                row.append($('<td />', {text: date, "class": 'col-md-3'}));

                for (keywordIndex = 0; keywordIndex < keywords.length; keywordIndex++) {
                    if (aggregations[date].hasOwnProperty(keywordIndex)) {
                        for (screenName in aggregations[date][keywordIndex]) {
                            row.append(
                                $('<td />', {
                                    html: '<a href="https://twitter.com/' + screenName + '">@' +
                                        screenName + ' mentioned "' + keywords[keywordIndex] + '"' +
                                        ' (' + aggregations[date][keywordIndex][screenName] + '&nbsp;occurrences)' + '</a>'
                                })
                            );
                        }
                    }
                }

                tableBody.append(row);
            }
        }
    });
})(keywords, since, until);