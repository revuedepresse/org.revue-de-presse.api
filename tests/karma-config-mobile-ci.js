'use strict';

var Configurator = require('./karma-config-mobile');

module.exports = function (karmaConfig) {
    var testedComponent = 'status';
    var testSuite = 'Status';
    var reportDirectory = '../build/karma';

    var configurator = Configurator(karmaConfig);
    configurator
        .shouldConfigureTestSuiteFor(testedComponent)
        .setReportDirectory(reportDirectory)
        .setTestSuiteName(testSuite)
        .setProfile(configurator.PROFILE_CI)
        .configure();
};
