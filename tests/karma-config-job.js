'use strict';

var Configurator = require('./karma-configurator');

module.exports = function (karmaConfig) {
    var testedComponent = 'job';

    var assetsDir = '../src/WeavingTheWeb/Bundle/DashboardBundle/' +
        'Resources/public';
    var componentTests = 'test-' + testedComponent + '.js';
    var testedComponentsDir = assetsDir + '/js';
    var vendorComponentsDir = assetsDir + '/components';

    var jqueryNativeAjax = vendorComponentsDir +
        '/jquery-ajax-native/src/jquery-ajax-native.js';
    var uuid = vendorComponentsDir + '/node-uuid/uuid.js';
    var testedComponentPath = testedComponentsDir + '/' + testedComponent + '.js';

    var configurator = Configurator(karmaConfig);
    configurator
        .shouldConfigureTestSuiteFor(testedComponent)
        .setFiles([
            'bower_components/jquery/dist/jquery.js',
            'bower_components/bind-polyfill/index.js',
            'bower_components/jquery-mockjax/dist/jquery.mockjax.js',
            jqueryNativeAjax,
            uuid,
            testedComponentPath,
            'request-mock.js',
            componentTests
        ])
        .preprocessScripts([
            testedComponentPath,
            'request-mock.js',
            componentTests
        ])
        .configure();

    return (function (karmaConfig) {
        return configurator.setConfig(karmaConfig);
    })(karmaConfig);
};
