'use strict';

/**
 * Export Karma configuration settings provider
 *
 * @see https://karma-runner.github.io/0.12/config/configuration-file.html
 *
 * @param {Object} config
 * @returns {SettingsProvider}
 */
module.exports = function (config) {

    /**
     * @param {Object} config
     * @constructor
     */
    var SettingsProvider = function (config) {
        this.defaultSettings = {

            // base path, that will be used to resolve files and exclude
            basePath: '',

            // frameworks to use
            frameworks: ['jasmine'],

            // list of files / patterns to load in the browser
            files: [],

            // test results reporter to use
            // possible values: 'dots', 'progress', 'junit', 'growl', 'coverage'
            reporters: ['spec'],

            // web server port
            port: 9876,

            // enable / disable colors in the output (reporters and logs)
            colors: true,

            // level of logging
            // possible values: config.LOG_DISABLE || config.LOG_ERROR || config.LOG_WARN || config.LOG_INFO || config.LOG_DEBUG
            logLevel: config.LOG_INFO,

            // enable / disable watching file and executing tests whenever any file changes
            autoWatch: true,

            // Start these browsers, currently available:
            // - Chrome
            // - ChromeCanary
            // - Firefox
            // - Opera (has to be installed with `npm install karma-opera-launcher`)
            // - Safari (only Mac; has to be installed with `npm install karma-safari-launcher`)
            // - PhantomJS
            // - IE (only Windows; has to be installed with `npm install karma-ie-launcher`)
            browsers: ['ChromeCanary'],

            eslint: {
                stopOnError: false,
                stopOnWarning: false
            },

            preprocessors: {},

            junitReporter: {},

            // If browser does not capture in given timeout [ms], kill it
            captureTimeout: 60000,

            // Continuous Integration mode
            // if true, it capture browsers, run tests and exit
            singleRun: false
        };
    };

    /**
     * Get sensitive default settings
     *
     * @returns {Object}
     */
    SettingsProvider.prototype.getDefaultSettings = function () {
        return this.defaultSettings;
    };

    return new SettingsProvider(config);
};
