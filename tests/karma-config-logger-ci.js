'use strict';

var Configurator = require('./karma-config-logger');

module.exports = function (karmaConfig) {
    var testSuite = 'Logger';
    var reportDirectory = '../build/karma';

    var configurator = Configurator(karmaConfig);
    configurator
        .setReportDirectory(reportDirectory)
        .setTestSuiteName(testSuite)
        .setProfile(configurator.PROFILE_CI)
        .configure();
};
