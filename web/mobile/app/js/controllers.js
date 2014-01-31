'use strict';

/* Controllers */

var twitterControllers = angular.module('twitterControllers', []);

twitterControllers.controller('ShowTweetsAction', ['$scope', '$http', '$location', '$routeParams',
    function ($scope, $http, $location, $routeParams) {
        var host,
            showLatestTweetsUrl; 
        
        host = $location.protocol() + '://' + $location.host();
        showLatestTweetsUrl = host + '/twitter/tweet/latest?username=' + $routeParams.username;

        $http.get(showLatestTweetsUrl).success(function (data) {
            $scope.tweets = data
        });

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
