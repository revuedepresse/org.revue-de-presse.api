'use strict';

/* Controllers */

function ShowRepositoriesAction($scope, $http) {
    var getRepositoriesUrl = Routing.generate('get_repositories', {});

    $http.get(getRepositoriesUrl).success(function (data) {
        $scope.dashboard = data[0]
    })
}
ShowRepositoriesAction.$inject = ['$scope', '$http'];
