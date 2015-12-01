'use strict';

var Configurator = require('./karma-config-perspective');
var getCustomLaunchers = require('./get-custom-launchers');

module.exports = function (karmaConfig) {
    var testedComponent = 'perspective';
    var testSuite = 'Perspective';

    var configurator = Configurator(karmaConfig);
    configurator
        .shouldConfigureTestSuiteFor(testedComponent)
        .setTestSuiteName(testSuite)
        .setCustomLaunchers(getCustomLaunchers())
        .setProfile(configurator.PROFILE_SAUCE_LABS)
        .setSauceLabsOptions({timeZone: 'Paris'})
        .shouldStartSauceConnect(false)
        .setUpSauceLabsForTravis()
        .configure();
};
