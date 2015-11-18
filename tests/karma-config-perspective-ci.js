'use strict';

var Configurator = require('./karma-config-perspective');

module.exports = function (karmaConfig) {
    var testedComponent = 'perspective';
    var testSuite = 'Perspective';
    var reportDirectory = '../build/karma';

    var configurator = Configurator(karmaConfig);
    configurator
        .shouldConfigureTestSuiteFor(testedComponent)
        .setReportDirectory(reportDirectory)
        .setTestSuiteName(testSuite)
        .setProfile(configurator.PROFILE_CI)
        .configure();
};
