'use strict';

var githubRepositories = angular.module('githubRepositories', []);

githubRepositories.controller('ShowRepositoriesAction', [
    '$scope', '$http',
    function ($scope, $http) {
        var getRepositoriesUrl = Routing.generate('weaving_the_web_api_get_repositories', { limit: 15 });

        $http.get(getRepositoriesUrl).success(function (data) {
            $scope.dashboard = data
        });
    }
]);
