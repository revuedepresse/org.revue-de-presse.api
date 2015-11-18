'use strict';

var Configurator = require('./karma-configurator');

module.exports = function (karmaConfig) {
    var assetsDir = '../src/WeavingTheWeb/Bundle/DashboardBundle/' +
        'Resources/public';
    var testedComponentsDir = assetsDir + '/js';
    var vendorComponentsDir = assetsDir + '/components';

    var queryParams = testedComponentsDir + '/get-query-params.js';
    var logger = testedComponentsDir + '/logger.js';
    var dateFormatter = testedComponentsDir + '/format-date.js';
    var metrics = testedComponentsDir + '/metrics.js';

    var configurator = Configurator(karmaConfig);
    configurator
        .setFiles([
            'bower_components/jquery/dist/jquery.js',
            'bower_components/bind-polyfill/index.js',
            vendorComponentsDir + '/clipboard/dist/clipboard.js',
            vendorComponentsDir + '/d3/d3.js',
            vendorComponentsDir + '/metrics-graphics/dist/metricsgraphics.js',
            vendorComponentsDir + '/file-saver.js/FileSaver.js',
            vendorComponentsDir + '/comma-separated-values/csv.min.js',
            queryParams,
            logger,
            dateFormatter,
            metrics,
            'test-format-date.js',
            'test-get-query-params.js'
        ])
        .preprocessScripts([
            queryParams,
            logger,
            dateFormatter,
            metrics,
            'test-format-date.js',
            'test-get-query-params.js'
        ])
        .configure();

    return (function (karmaConfig) {
        return configurator.setConfig(karmaConfig);
    })(karmaConfig);
};
