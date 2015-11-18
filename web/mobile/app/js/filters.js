'use strict';

(function (require) {
    var angular = require.angular;
    angular.module('strictHttpsFilter', []).filter('strict_https', function () {
        return function (input) {
            return input.replace('http', 'https');
        };
    });
})(this);
