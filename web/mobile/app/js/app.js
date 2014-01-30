'use strict';

var weaverApp = angular.module('weaverApp', [
    'ngRoute',
    'twitterControllers'
]);

weaverApp.config(
    ['$routeProvider', function($routeProvider) {
        $routeProvider.when('/:username', {
            templateUrl: '/mobile/app/partials/tweets.html',
            controller: 'ShowTweetsAction'
        });
        $routeProvider.otherwise({redirectTo: '/'});
    }]);