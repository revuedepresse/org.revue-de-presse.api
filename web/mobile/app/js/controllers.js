'use strict';

/* Controllers */

var twitterControllers = angular.module('twitterControllers', []),
    baseUrl = 'https://## FILL HOSTNAME ##';

twitterControllers.controller('ShowTweetsAction', ['$scope', '$http',
    function ($scope, $http) {
        var token = '## FILL ME ##',
            showLatestTweetsUrl = baseUrl + '/twitter/tweet/latest?token=' + token;
        $http.get(showLatestTweetsUrl).success(function (data) {
            $scope.tweets = data
        });

        $scope.star = function (tweetId, index) {
            var startTweetUrl = baseUrl + '/twitter/tweet/star/' + tweetId;
            $http.post(startTweetUrl).success(function () {
                $scope.tweets[index].starred = true;
            })
        }

        $scope.unstar = function (tweetId, index) {
            var startTweetUrl = baseUrl + '/twitter/tweet/unstar/' + tweetId;
            $http.post(startTweetUrl).success(function () {
                $scope.tweets[index].starred = false;
            })
        }
    }
]);
