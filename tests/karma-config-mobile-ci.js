'use strict';

var Configurator = require('./karma-config-mobile');
var getCustomLaunchers = require('./get-custom-launchers');

module.exports = function (karmaConfig) {
    var testedComponent = 'status';
    var testSuite = 'Status';

    var configurator = Configurator(karmaConfig);
    configurator
        .setTestSuiteName(testSuite)
        .shouldConfigureTestSuiteFor(testedComponent)
        .setCustomLaunchers(getCustomLaunchers())
        .setProfile(configurator.PROFILE_SAUCE_LABS)
        .shouldStartSauceConnect(false)
        .setUpSauceLabsForTravis()
        .configure();
};
