'use strict';

angular.module('WTW', ['WTW.filters']).config(
    ['$routeProvider', function($routeProvider) {
        $routeProvider.when('/github-repositories', {
            templateUrl: partials + 'repositories.html',
            controller: ShowRepositoriesAction
        });
        $routeProvider.otherwise({redirectTo: '/github-repositories'});
    }]);
