
(function (MG, document, fileSaver, debug) {
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
    var metric;
    var stringIdAccessor = 'id_str';

    var clipboard;
    var copyStatusButtonSelector = '#copy-status';
    var statusClipboardSelector = '#status-clipboard';

    var setUpClipboard = function () {
        clipboard = new Clipboard(copyStatusButtonSelector);
        clipboard.destroy();
        clipboard = new Clipboard(copyStatusButtonSelector);
    };

    var setUpExportAsCsv = function (timeSeries, $, fileSaver) {
        $('#export-as-csv').click(function () {
            var head;
            var commaSeparatedRows = timeSeries.map(function (row, index) {
                var rowCells;
                if (index === 0) {
                    head = Object.keys(row);
                    rowCells = head;
                } else {
                    var columnIndex;
                    rowCells = [];
                    for (columnIndex = 0; columnIndex < head.length; columnIndex++) {
                        var columnName = head[columnIndex];
                        if (columnName == 'user') {
                            rowCells.push('"' + row[columnName].screen_name + '"');
                        } else {
                            rowCells.push('"' + row[columnName] + '"');
                        }

                        if (columnName == 'text') {
                            rowCells[rowCells.length - 1] = rowCells[rowCells.length - 1].replace(/\r\n/g, '\n');
                        }
                    }
                }
                return rowCells.join(',');
            });
            var csvDocument = commaSeparatedRows.join('\r\n');
            fileSaver(new Blob([csvDocument], {type: 'text/csv'}), 'perspective.csv');
        });
    };

    var queryParams = getQueryParams();
    if ('metric' in queryParams) {
        metric = queryParams.metric;
    } else {
        metric = 'retweet_count';
    }

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

        if (metric in status) {
            var user = status.user;
            var statusId = status[stringIdAccessor];
            var link = 'https://twitter.com/' + user.screen_name + '/status/' + statusId;

            timeSeries.push({
                interest: status[metric],
                date: status[dateAccessor],
                text: status[textAccessor],
                link: link,
                user: user
            });
        }
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
            var rows = [
                'Author: ' + d.user.name + ' (@' + d.user.screen_name + ')',
                'Status: ' + d.text,
                'Retweets: ' + d.interest
            ];
            $('#status').text(rows.join("\n"));

            $(statusClipboardSelector).val(d.link);
            $(copyStatusButtonSelector).attr('data-clipboard-text', d.link);
            $(statusClipboardSelector).focus();
        },
        target: document.getElementById('graph'),
        x_accessor: 'date',
        y_accessor: 'interest'
    });

    setUpClipboard();
    setUpExportAsCsv(timeSeries, $, fileSaver);

})(MG, window.document, window.saveAs, false);
