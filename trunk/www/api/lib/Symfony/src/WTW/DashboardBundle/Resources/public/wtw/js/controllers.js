'use strict';

/* Controllers */

function ShowRepositoriesAction($scope, $http) {
    $http.get('http://## FILL HOSTNAME ##/sf2/api/github/repositories').success(function (data) {
        $scope.dashboard = data[0]
    })
}
ShowRepositoriesAction.$inject = ['$scope', '$http'];
