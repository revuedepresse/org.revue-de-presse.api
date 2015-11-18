'use strict';

var SettingsProvider = require('./karma-settings-provider');

/**
 * Export Karma test suite configurator
 *
 * @see https://karma-runner.github.io
 *
 * @param config
 * @returns {Configurator}
 */
module.exports = function (config) {

    /**
     * @param {Object} config
     * @constructor
     */
    var Configurator = function (config) {
        this.PROFILE_CI = 'continuous integration profile';
        this.PROFILE_TDD = 'default profile for test driven development';

        this.config = config;
        this.profile = this.PROFILE_TDD;

        this.settingsProvider = SettingsProvider(this.config);
        this.junitReporter = {};
        this.preprocessors = {};
        this.files = [];
    };

    /**
     * @see https://karma-runner.github.io/0.12/config/configuration-file.html
     *
     * @param {Boolean} autoWatch
     * @returns {Configurator}
     */
    Configurator.prototype.setAutoWatch = function (autoWatch) {
        this.autoWatch = autoWatch;

        return this;
    };

    /**
     * @see https://karma-runner.github.io/0.12/config/configuration-file.html
     *
     * @param {Object} config
     * @returns {Configurator}
     */
    Configurator.prototype.setConfig = function (config) {
        this.config = config;

        return this;
    };

    /**
     * Set ESLint config.
     *
     * @see http://eslint.org/
     *
     * @param {Object} config
     * @returns {Configurator}
     */
    Configurator.prototype.setEslintConfig = function (config) {
        this.eslint = config;

        return this;
    };

    /**
     * @see https://karma-runner.github.io/0.12/config/configuration-file.html
     *
     * @param {Array} files
     * @return {Configurator}
     */
    Configurator.prototype.excludeFiles = function (files) {
        this.exclude = files;

        return this;
    };

    /**
     * e.g.
     *
     * ```
     * var reporters = [
     *     'junit',    // continuous integration
     *     'spec'      // BDD
     * ];
     * ```
     *
     * @see https://karma-runner.github.io/0.12/config/configuration-file.html
     *
     * @param {Array} reporters
     * @returns {Configurator}
     */
    Configurator.prototype.setReporters = function (reporters) {
        this.reporters = reporters;

        return this;
    };

    /**
     * Set scripts processed by ESLint for static analysis reporting.
     *
     * @param {Array} scripts
     * @returns {Configurator}
     */
    Configurator.prototype.preprocessScripts = function (scripts) {
        var i;
        for (i = 0; i < scripts.length; i++) {
            this.preprocessors[scripts[i]] = ['eslint'];
        }

        return this;
    };

    /**
     * Set the name of a component to be tested.
     *
     * e.g.
     *
     * ```
     * var component = 'the-name-of-a-module-to-be-tested-without-file-extension';
     * ```
     *
     * @param {String} component
     * @returns {Configurator}
     */
    Configurator.prototype.shouldConfigureTestSuiteFor = function (component) {
        this.testedComponentName = component;

        return this;
    };

    /**
     * Set the directory where report files should be exported to.
     * The 'junit' reporter, output file and test suite name
     * should also be set along with the report directory.
     *
     * @see https://karma-runner.github.io/0.12/config/configuration-file.html
     *
     * @param {String} directory
     * @returns {Configurator}
     */
    Configurator.prototype.setReportDirectory = function (directory) {
        this.reportDirectory = directory;

        return this;
    };

    /**
     * Override the karma configuration properties with
     * settings explicitly provided to the configurator
     * or declared by default.
     *
     * @see {SettingsProvider}
     *
     * @returns {Configurator}
     */
    Configurator.prototype.updateSettingsParameters = function () {
        if (this.files.length === 0) {
            throw new Error('Missing files configuration');
        }
        if (Object.keys(this.preprocessors).length === 0) {
            throw new Error('Missing preprocessors configuration');
        }

        this.settings.files = this.files;
        this.settings.junitReporter = this.junitReporter;
        this.settings.preprocessors = this.preprocessors;

        var settings = [
            'autoWatch',
            'browsers',
            'eslint',
            'exclude',
            'logLevel',
            'reporters',
            'singleRun'
        ];
        var settingIndex;
        for (settingIndex = 0; settingIndex < settings.length;settingIndex++) {
            var settingName = settings[settingIndex];
            if (this[settingName] !== undefined) {
                this.settings[settingName] = this[settingName];
            }
        }

        return this;
    };

    /**
     * @see https://karma-runner.github.io/0.12/config/configuration-file.html
     *
     * @param {String} level
     * @returns {Configurator}
     */
    Configurator.prototype.setLogLevel = function (level) {
        this.logLevel = level;

        return this;
    };

    /**
     * Set browsers captured to run the test suite.
     *
     * e.g.
     *
     * ```
     * var browsers = [
     *     'ChromeCanary'
     *     'PhantomJS'
     * ];
     * ```
     *
     * @see https://karma-runner.github.io/0.12/config/configuration-file.html
     *
     * @param {Array} browsers
     * @returns {Configurator}
     */
    Configurator.prototype.setBrowsers = function (browsers) {
        this.browsers = browsers;

        return this;
    };

    /**
     * Set the path to the report file output by 'junit' reporter.
     *
     * @see {Configurator.setReportDirectory}
     *
     * @param {string} outputFile
     * @returns {Configurator}
     */
    Configurator.prototype.setOutputFile = function (outputFile) {
        this.junitReporter.outputFile = outputFile;

        return this;
    };

    /**
     * @see https://karma-runner.github.io/0.12/config/configuration-file.html
     *
     * @param {Boolean} singleRun
     * @returns {Configurator}
     */
    Configurator.prototype.setSingleRun = function (singleRun) {
        this.singleRun = singleRun;

        return this;
    };

    /**
     * @see https://karma-runner.github.io/0.12/config/configuration-file.html
     *
     * @param {String} name
     * @returns {Configurator}
     */
    Configurator.prototype.setTestSuiteName = function (name) {
        this.junitReporter.suite = name;

        return this;
    };

    /**
     * Override Karma configuration settings for continuous integration.
     *
     * @returns {Configurator}
     */
    Configurator.prototype.applyContinuousIntegrationProfile = function () {
        if (this.testedComponentName === undefined) {
            throw new Error('Missing tested component name');
        }

        this.setAutoWatch(false);
        this.setBrowsers(['PhantomJS']);
        this.setEslintConfig({
            stopOnWarning: true,
            stopOnError: true
        });
        this.setLogLevel(this.config.LOG_ERROR);
        this.setOutputFile(this.reportDirectory + '/' +
            this.testedComponentName + '-test-results.xml');
        this.setReporters(['spec', 'junit']);
        this.setSingleRun(true);

        return this;
    };

    /**
     * Set the configuration profile to be used
     * to override Karma configuration with sensitive defaults.
     *
     * e.g.
     *
     * ```
     * var profile = Configurator.PROFILE_CI;
     * ```
     *
     * @param profile
     * @returns {Configurator}
     */
    Configurator.prototype.setProfile = function (profile) {
        this.profile = profile;

        return this;
    };

    /**
     * Apply Karma configuration.
     *
     * @returns {*}
     */
    Configurator.prototype.configure = function () {
        this.settings = this.settingsProvider.getDefaultSettings();
        if (this.profile !== this.PROFILE_TDD) {
            if (this.profile !== this.PROFILE_CI) {
                throw new Error('Invalid configuration profile.' + '\n' +
                    'Please consider using one of those: ' + '\n' +
                    ' - Configurator.PROFILE_CI' + '\n' +
                    ' - Configurator.PROFILE_TDD' + '\n' +
                    'or do not call "setProfile" on instance of Configurator');
            } else {
                this.applyContinuousIntegrationProfile();
            }
        }
        this.updateSettingsParameters();

        return this.config.set(this.settings);
    };

    /**
     * Set files required by the configured test suite:
     * - vendors,
     * - mocks,
     * - tested components and
     * - tests.
     *
     * @param {Array} files
     * @returns {Configurator}
     */
    Configurator.prototype.setFiles = function (files) {
        this.files = files;

        return this;
    };

    return new Configurator(config);
};
