'use strict';

angular.module('strictHttpsFilter', []).filter('strict_https', function () {
    return function (input) {
        return input.replace('http', 'https');
    };
});