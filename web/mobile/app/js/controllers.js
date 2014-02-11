'use strict';

/* Controllers */

var twitterControllers = angular.module('twitterControllers', []);

twitterControllers.controller('ShowTweetsAction', ['$scope', '$http', '$location', '$routeParams', '$log', 'twitter',
    function ($scope, $http, $location, $routeParams, $log, twitter) {
        var host = $location.protocol() + '://' + $location.host();

        twitter.showMoreTweets($http, $scope, $location, $routeParams, $log);

        $scope.star = function (statusId, index) {
            var startTweetUrl = host + '/twitter/tweet/star/' + statusId;
            $http.post(startTweetUrl).success(function () {
                $scope.tweets[index].starred = true;
            }).error(function (data) {
                if ($log !== undefined) {
                    $log.error(data)
                }
            });
        }

        $scope.unstar = function (statusId, index) {
            var startTweetUrl = host + '/twitter/tweet/unstar/' + statusId;
            $http.post(startTweetUrl).success(function () {
                $scope.tweets[index].starred = false;
            }).error(function (data) {
                if ($log !== undefined) {
                    $log.error(data)
                }
            });
        }
    }
]);
