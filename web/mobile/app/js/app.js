'use strict';

var weaverApp = angular.module('weaverApp', [
    'ngRoute',
    'infinite-scroll',
    'jmdobry.angular-cache',
    'strictHttpsFilter',
    'twitterStatuses',
    'twitterBookmarks'
]);

weaverApp.config(['$routeProvider', function($routeProvider) {
    $routeProvider.when('/:username', {
        templateUrl: '/mobile/app/partials/statuses.html',
        controller: 'ShowStatusesAction'
    });
    $routeProvider.when('/bookmarks/:username', {
        templateUrl: '/mobile/app/partials/bookmarks.html',
        controller: 'ShowBookmarksAction'
    });
    $routeProvider.otherwise({redirectTo: '/'});
}]);