'use strict';

var Configurator = require('./karma-configurator');

module.exports = function(karmaConfig) {
    var assetsDir = '../web/mobile/app';
    var testedComponentsDir = assetsDir + '/js';
    var vendorComponentsDir = assetsDir + '/lib';
    var excludedFiles = ['../web/mobile/app/js/script.js']

    var configurator = Configurator(karmaConfig);
    configurator
        .setFiles([
            vendorComponentsDir + '/lodash.min.js',
            vendorComponentsDir + '/angular/angular.js',
            vendorComponentsDir + '/angular/angular-route.js',
            vendorComponentsDir + '/ng-infinite-scroll.min.js',
            vendorComponentsDir + '/ng-cache.js',
            'bower_components/angular-mocks/angular-mocks.js',
            testedComponentsDir + '/*.js',
            testedComponentsDir + '/controllers/*.js',
            testedComponentsDir + '/services/*.js',
            'location-mock.js',
            'test-mobile-*.js'
        ])
        .excludeFiles(excludedFiles)
        .preprocessScripts([
            testedComponentsDir + '/*.js',
            testedComponentsDir + '/controllers/*.js',
            testedComponentsDir + '/services/*.js',
            'location-mock.js',
            'test-mobile-*.js'
        ])
        .configure();

    return (function (karmaConfig) {
        return configurator.setConfig(karmaConfig);
    })(karmaConfig);
};
