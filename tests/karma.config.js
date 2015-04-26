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
            '../web/mobile/app/lib/lodash.min.js',
            '../web/mobile/app/lib/angular/angular.js',
            '../web/mobile/app/lib/angular/angular-route.js',
            '../web/mobile/app/lib/ng-infinite-scroll.min.js',
            '../web/mobile/app/lib/ng-cache.js',
            '../web/mobile/app/js/*.js',
            '../web/mobile/app/js/controllers/*.js',
            '../web/mobile/app/js/services/*.js',
            'bower_components/angular-mocks/angular-mocks.js',
            'location-mock.js',
            'test-*.js'
        ],


        // list of files to exclude
        exclude: [
            '../web/mobile/app/js/script.js'
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
        browsers: ['ChromeCanary'],

        junitReporter: {
            outputFile: '../build/mobile-test-results.xml',
            suite: 'Weaving the Web Mobile'
        },

        // If browser does not capture in given timeout [ms], kill it
        captureTimeout: 60000,


        // Continuous Integration mode
        // if true, it capture browsers, run tests and exit
        singleRun: false
    });
};
