'use strict';

/* Controllers */

function ShowRepositoriesAction($scope, $http) {
    var getRepositoriesUrl = Routing.generate('weaving_the_web_api_get_repositories', { limit: 2000 });

    $http.get(getRepositoriesUrl).success(function (data) {
        $scope.dashboard = data
    })
}
ShowRepositoriesAction.$inject = ['$scope', '$http'];
