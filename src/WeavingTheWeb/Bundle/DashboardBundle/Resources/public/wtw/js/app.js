'use strict';

var weaverApp = angular.module('weaverApp', [
    'ngRoute',
    'repositoriesFilters',
    'githubRepositories'
]);

weaverApp.config(['$routeProvider', function($routeProvider) {
    $routeProvider.when('/github-repositories', {
        templateUrl: partials + 'repositories.html',
        controller: 'ShowRepositoriesAction'
    });
    $routeProvider.otherwise({redirectTo: '/github-repositories'});
}]);

weaverApp.config(function ($httpProvider) {
    $httpProvider.interceptors.push('oauthAuthentication');
});