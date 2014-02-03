'use strict';

/* Controllers */

var twitterControllers = angular.module('twitterControllers', []);

twitterControllers.controller('ShowTweetsAction', ['$scope', '$http', '$location', '$routeParams', '$log', 'twitter',
    function ($scope, $http, $location, $routeParams, $log, twitter) {
        var host = $location.protocol() + '://' + $location.host();

        twitter.showMoreTweets($http, $scope, $location, $routeParams, $log);

        $scope.star = function (tweetId, index) {
            var startTweetUrl = host + '/twitter/tweet/star/' + tweetId;
            $http.post(startTweetUrl).success(function () {
                $scope.tweets[index].starred = true;
            })
        }

        $scope.unstar = function (tweetId, index) {
            var startTweetUrl = host + '/twitter/tweet/unstar/' + tweetId;
            $http.post(startTweetUrl).success(function () {
                $scope.tweets[index].starred = false;
            })
        }
    }
]);
