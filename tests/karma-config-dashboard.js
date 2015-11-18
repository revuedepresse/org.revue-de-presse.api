'use strict';

var Configurator = require('./karma-configurator');

module.exports = function(karmaConfig) {
    var assetsDir = '../src/WeavingTheWeb/Bundle/DashboardBundle/' +
        'Resources/public';
    var testedComponentsDir = assetsDir + '/js';

    var dashboard = testedComponentsDir + '/dashboard.js';
    var editableContent = testedComponentsDir + '/editable-content.js';
    var notificationCenter = testedComponentsDir + '/notification-center.js';

    var requestMock = 'request-mock.js';
    var notificationCenterMock = 'notification-center-mock.js';

    var configurator = Configurator(karmaConfig);
    configurator
        .setFiles([
            'bower_components/jquery/dist/jquery.js',
            'bower_components/jquery-mockjax/dist/jquery.mockjax.js',
            'bower_components/bind-polyfill/index.js',
            editableContent,
            dashboard,
            notificationCenter,
            notificationCenterMock,
            requestMock,
            'test-dashboard.js',
            'test-editable-content.js'
        ])
        .preprocessScripts([
            editableContent,
            dashboard,
            notificationCenter,
            notificationCenterMock,
            requestMock,
            'test-dashboard.js',
            'test-editable-content.js'
        ])
        .configure();

    return (function (karmaConfig) {
        return configurator.setConfig(karmaConfig);
    })(karmaConfig);
};
