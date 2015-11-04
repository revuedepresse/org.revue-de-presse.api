// Karma configuration
// Generated on Mon Feb 03 2014 19:57:02 GMT+0100 (CET)

module.exports = function(config) {
    config.set({

        // base path, that will be used to resolve files and exclude
        basePath: '',


        // frameworks to use
        frameworks: ['jasmine'],


        // list of files / patterns to load in the browser
        files: [
            'bower_components/jquery/dist/jquery.js',
            '../src/WeavingTheWeb/Bundle/DashboardBundle/Resources/public/components/clipboard/dist/clipboard.js',
            '../src/WeavingTheWeb/Bundle/DashboardBundle/Resources/public/components/d3/d3.js',
            '../src/WeavingTheWeb/Bundle/DashboardBundle/Resources/public/components/metrics-graphics/dist/metricsgraphics.js',
            '../src/WeavingTheWeb/Bundle/DashboardBundle/Resources/public/components/file-saver.js/FileSaver.js',
            '../src/WeavingTheWeb/Bundle/DashboardBundle/Resources/public/components/comma-separated-values/csv.min.js',
            '../src/WeavingTheWeb/Bundle/DashboardBundle/Resources/public/js/get-query-params.js',
            '../src/WeavingTheWeb/Bundle/DashboardBundle/Resources/public/js/logger.js',
            '../src/WeavingTheWeb/Bundle/DashboardBundle/Resources/public/js/format-date.js',
            '../src/WeavingTheWeb/Bundle/DashboardBundle/Resources/public/js/metrics.js',
            'test-format-date.js',
            'test-get-query-params.js'
        ],


        // test results reporter to use
        // possible values: 'dots', 'progress', 'junit', 'growl', 'coverage'
        reporters: ['spec', 'junit'],

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
        browsers: ['PhantomJS'],

        eslint: {
            stopOnError: false,
            stopOnWarning: true
        },

        preprocessors: {
            '../src/WeavingTheWeb/Bundle/DashboardBundle/Resources/public/js/metrics.js':           ['eslint'],
            '../src/WeavingTheWeb/Bundle/DashboardBundle/Resources/public/js/get-query-params.js':  ['eslint'],
            '../src/WeavingTheWeb/Bundle/DashboardBundle/Resources/public/js/format-date.js':       ['eslint'],
            '../src/WeavingTheWeb/Bundle/DashboardBundle/Resources/public/js/logger.js':            ['eslint']
        },

        junitReporter: {
            outputFile: '../build/karma/perspective-test-results.xml',
            suite: 'Perspective'
        },

        // If browser does not capture in given timeout [ms], kill it
        captureTimeout: 60000,


        // Continuous Integration mode
        // if true, it capture browsers, run tests and exit
        singleRun: true
    });
};
