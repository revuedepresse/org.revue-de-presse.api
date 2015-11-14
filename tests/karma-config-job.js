// Karma configuration
// Generated on Mon Feb 03 2014 19:57:02 GMT+0100 (CET)

module.exports = function(config) {
    var testedComponentsDir = '../src/WeavingTheWeb/Bundle/DashboardBundle/' +
      'Resources/public/js';
    var notificationCenter = testedComponentsDir + '/notification-center.js';
    var job = testedComponentsDir + '/job.js';
    var preprocessedScripts = [
        notificationCenter,
        job,
        'request-mock.js',
        'test-job.js'
    ];
    var preprocessors = {};
    for (i = 0; i < preprocessedScripts.length; i++) {
        preprocessors[preprocessedScripts[i]] = ['eslint'];
    }

    config.set({

        // base path, that will be used to resolve files and exclude
        basePath: '',


        // frameworks to use
        frameworks: ['jasmine'],


        // list of files / patterns to load in the browser
        files: [
            'bower_components/jquery/dist/jquery.js',
            'bower_components/bind-polyfill/index.js',            
            notificationCenter,
            job,
            'bower_components/jquery-mockjax/dist/jquery.mockjax.js',
            'request-mock.js',
            'test-job.js'
        ],

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
        browsers: ['PhantomJS', 'ChromeCanary'],

        eslint: {
            stopOnError: false,
            stopOnWarning: false
        },

        preprocessors: preprocessors,

        junitReporter: {
            outputFile: '../build/karma/job-test-results.xml',
            suite: 'Job'
        },

        // If browser does not capture in given timeout [ms], kill it
        captureTimeout: 60000,


        // Continuous Integration mode
        // if true, it capture browsers, run tests and exit
        singleRun: false
    });
};
