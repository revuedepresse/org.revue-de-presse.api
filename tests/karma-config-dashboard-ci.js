'use strict';

var Configurator = require('./karma-config-dashboard');

module.exports = function (karmaConfig) {
    var testedComponent = 'dashboard';
    var testSuite = 'Dashboard';
    var reportDirectory = '../build/karma';

    var configurator = Configurator(karmaConfig);
    configurator
        .shouldConfigureTestSuiteFor(testedComponent)
        .setReportDirectory(reportDirectory)
        .setTestSuiteName(testSuite)
        .setProfile(configurator.PROFILE_CI)
        .configure();
};
