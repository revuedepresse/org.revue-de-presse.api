'use strict';

var Configurator = require('./karma-config-dashboard');
var getCustomLaunchers = require('./get-custom-launchers');

module.exports = function (karmaConfig) {
    var testedComponent = 'dashboard';
    var testSuite = 'Dashboard';

    var configurator = Configurator(karmaConfig);
    configurator
        .shouldConfigureTestSuiteFor(testedComponent)
        .setTestSuiteName(testSuite)
        .setCustomLaunchers(getCustomLaunchers())
        .setProfile(configurator.PROFILE_SAUCE_LABS)
        .shouldStartSauceConnect(false)
        .setUpSauceLabsForTravis()
        .configure();
};
