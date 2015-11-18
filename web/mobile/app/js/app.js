'use strict';

(function (exports, require) {
    var angular = require.angular;
    var weaverApp = angular.module('weaverApp', [
        'ngRoute',
        'infinite-scroll',
        'jmdobry.angular-cache',
        'strictHttpsFilter',
        'twitterStatuses',
        'twitterBookmarks'
    ]);

    weaverApp.config(['$routeProvider', function($routeProvider) {
        $routeProvider.when('/:username/:oauth_token', {
            controller: 'ShowStatusesAction',
            templateUrl: '/mobile/app/partials/statuses.html'
        });

        $routeProvider.when('/bookmarks/:username', {
            controller: 'ShowBookmarksAction',
            templateUrl: '/mobile/app/partials/bookmarks.html'
        });

        $routeProvider.otherwise({redirectTo: '/'});
    }]);

    weaverApp.config(['$httpProvider', function ($httpProvider) {
        $httpProvider.interceptors.push('oauthAuthentication');
    }]);

    exports.weaverApp = weaverApp;
})(this, this);
