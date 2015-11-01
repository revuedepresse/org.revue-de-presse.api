
(function (MG, document, debug) {
    var index;
    var hiddenCells = $('tr td .json');
    var totalDocuments = hiddenCells.length;
    var timeSeries = [];
    var cell;
    var rawDocument;
    var status;
    var regExp;
    var escapedDocument;
    var dateAccessor = 'created_at';
    var textAccessor = 'text';
    var metric = 'retweet_count';
    var stringIdAccessor = 'id_str';

    var clipboard;
    var copyStatusButtonSelector = '#copy-status';
    var statusClipboardSelector = '#status-clipboard';

    var setUpClipboard = function () {
        clipboard = new Clipboard(copyStatusButtonSelector);
        clipboard.destroy();
        clipboard = new Clipboard(copyStatusButtonSelector);
    };

    for (index = 1; index < totalDocuments; index++) {
        cell = hiddenCells[index];
        rawDocument = $(cell).text();
        try {
            status = JSON.parse(rawDocument);
        } catch (error) {
            if (debug) {
                console.log('Could not parse raw document at index {}'.replace('{}', index));
            }
            regExp = /:(\s*)"([^"]*)"([^",]*)"([^"]*)"/g;
            escapedDocument = rawDocument.replace(regExp, ':$1"$2\\\"$3\\\"$4"');
            status = JSON.parse(escapedDocument);
        }

        timeSeries.push({
            interest: status[metric],
            date: status[dateAccessor],
            text: status[textAccessor],
            status: status
        });
    }

    if (timeSeries.length === 0) {
        return;
    }

    timeSeries = MG.convert.date(timeSeries, 'date', '%a %b %e %H:%M:%S %Z %Y');

    MG.data_graphic({
        title: 'Perspective metrics',
        data: timeSeries,
        width: 1500,
        height: 500,
        right: 150,
        markers: [],
        mouseover: function(d) {
            var status = d.status;
            var user = status.user;
            var statusId = status[stringIdAccessor];
            var link = 'https://twitter.com/' + user.screen_name + '/status/' + statusId;
            var rows = [
                'Author: ' + user.name + ' (@' + user.screen_name + ')',
                'Status: ' + d.text,
                'Retweets: ' + d.interest
            ];
            $('#status').text(rows.join("\n"));

            $(statusClipboardSelector).val(link);
            $(copyStatusButtonSelector).attr('data-clipboard-text', link);
            $(statusClipboardSelector).focus();
        },
        target: document.getElementById('graph'),
        x_accessor: 'date',
        y_accessor: 'interest'
    });

    setUpClipboard();
})(MG, window.document, false);
