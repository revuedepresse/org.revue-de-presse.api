'use strict';

var Configurator = require('./karma-config-logger');
var getCustomLaunchers = require('./get-custom-launchers');

module.exports = function (karmaConfig) {
    var testSuite = 'Logger';

    var configurator = Configurator(karmaConfig);
    configurator
        .setTestSuiteName(testSuite)
        .setCustomLaunchers(getCustomLaunchers())
        .setProfile(configurator.PROFILE_SAUCE_LABS)
        .shouldStartSauceConnect(false)
        .setUpSauceLabsForTravis()
        .configure();
};
