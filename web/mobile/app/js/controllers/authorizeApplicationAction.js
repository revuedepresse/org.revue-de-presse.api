'use strict';

var authorizeApplication = angular.module('authorizeApplication', []);

authorizeApplication.controller('AuthorizeApplicationAction', [
    '$scope, $auth',
    function ($scope, $auth) {
        $scope.authenticate = function(provider) {
          $auth.authenticate(provider);
        };
    }
]);