'use strict';

var Configurator = require('./karma-config-job');

module.exports = function (karmaConfig) {
    var testSuite = 'Job';
    var reportDirectory = '../build/karma';

    var configurator = Configurator(karmaConfig);
    configurator
        .setReportDirectory(reportDirectory)
        .setTestSuiteName(testSuite)
        .setProfile(configurator.PROFILE_CI)
        .configure();
};
