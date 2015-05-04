var index;

d3.json('/api/twitter/aggregate/' + keywords.join(',') + '/2007-01-01/2016-01-01')
.header("Authorization", "Bearer " + token)
.get(function(error, data) {

    for (index = 0 ; index < keywords.length ; index++) {
        data[index] = MG.convert.date(data[index], 'date');
    }

    MG.data_graphic({
        title: 'Mentions in personal network',
        description: 'From 2007 to 2014',
        data: data,
        width: 1200,
        height: 500,
        right: 150,
        target: document.getElementById('time-series-mentions'),
        legend: keywords,
        x_accessor: 'date',
        y_accessor: 'mentions'
    });
});
