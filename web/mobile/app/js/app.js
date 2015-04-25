'use strict';

var weaverApp = angular.module('weaverApp', [
    'ngRoute',
    'infinite-scroll',
    'jmdobry.angular-cache',
    'strictHttpsFilter',
    'satellizer',
    'authorizeApplication',
    'twitterStatuses',
    'twitterBookmarks'
]);

weaverApp.config(['$authProvider', function($authProvider) {
    var clientId = '## FILL ME ##';

    $authProvider.oauth2({
      name: 'Weaving The Web',
      url: '/oauth/v2/auth',
      redirectUri: '/mobile/app.html',
      clientId: clientId,
      authorizationEndpoint: 'http://dashboard.dev/oauth/v2/auth'
    });
}]);

weaverApp.config(['$routeProvider', function($routeProvider) {
    $routeProvider.when('/:username', {
        controller: 'ShowStatusesAction',
        templateUrl: '/mobile/app/partials/statuses.html'
    });

    $routeProvider.when('/bookmarks/:username', {
        controller: 'ShowBookmarksAction',
        templateUrl: '/mobile/app/partials/bookmarks.html'
    });

    $routeProvider.when('/', {
        controller: 'AuthorizeApplicationAction',
        templateUrl: '/mobile/app/partials/sign-in.html'
    });

    $routeProvider.otherwise({redirectTo: '/'});
}]);

weaverApp.config(['$httpProvider', function ($httpProvider) {
    $httpProvider.interceptors.push('oauthAuthentication');
}]);
