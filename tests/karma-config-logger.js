'use strict';

var Configurator = require('karma-configurator');

module.exports = function (karmaConfig) {
    var testedComponent = 'logger';

    var componentTests = 'test-' + testedComponent + '.js';
    var configurator = Configurator(karmaConfig);

    var assetsDir = '../src/WeavingTheWeb/Bundle/DashboardBundle/' +
        'Resources/public';
    var testedComponentsDir = assetsDir + '/js';
    var testedComponentPath = testedComponentsDir + '/' + testedComponent + '.js'

    configurator
        .shouldConfigureTestSuiteFor(testedComponent)
        .setFiles([
            'bower_components/jquery/dist/jquery.js',
            'bower_components/bind-polyfill/index.js',
            testedComponentPath,
            componentTests
        ])
        .preprocessScripts([
            testedComponentPath,
            componentTests
        ])
        .configure();

    return (function (karmaConfig) {
        return configurator.setConfig(karmaConfig);
    })(karmaConfig);
};
