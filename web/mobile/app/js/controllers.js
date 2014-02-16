'use strict';

/* Controllers */

var twitterControllers = angular.module('twitterControllers', []);

twitterControllers.controller('ShowTweetsAction', [
    '$scope', '$http', '$location', '$log', '$routeParams', 'twitter', 'offlineCache',
    function ($scope, $http, $location, $log, $routeParams, twitter, offlineCache) {
        var host = $location.protocol() + '://' + $location.host();
        var cache = offlineCache.getLocalStorageCache();

        twitter.showMoreTweets($scope, $routeParams);

        $scope.star = function (statusId, index) {
            var starTweetUrl = host + '/twitter/tweet/star/' + statusId;
            cache.put(statusId, {'starred': true});
            $http.post(starTweetUrl).success(function () {
                $scope.tweets[index].starred = true;
            }).error(function (data) {
                if ($log !== undefined) {
                    $log.error(data)
                }
            });
        }

        $scope.unstar = function (statusId, index) {
            var unstarTweetUrl = host + '/twitter/tweet/unstar/' + statusId;
            cache.put(statusId, {'starred': false});
            $http.post(unstarTweetUrl).success(function () {
                $scope.tweets[index].starred = false;
            }).error(function (data) {
                if ($log !== undefined) {
                    $log.error(data)
                }
            });
        }
    }
]);

twitterControllers.controller('SyncTweetsStarringStatusAction', [
    '$scope', '$http', '$location', '$log', 'offlineCache',
    function ($scope, $http, $location, $log, offlineCache) {
        var cache = offlineCache.getLocalStorageCache();

        $scope.sync = function () {
            var host = $location.protocol() + '://' + $location.host();
            var keys = cache.keys();
            var starTweetUrlTemplate = host + '/twitter/tweet/star/';
            var unstarTweetUrlTemplate = host + '/twitter/tweet/unstar/';

            keys.forEach(function (statusId) {
                var actionUrl;
                var tweetStatus = cache.get(statusId);

                if (tweetStatus.starred) {
                    actionUrl = starTweetUrlTemplate + statusId;
                } else if (!tweetStatus.starred) {
                    actionUrl = unstarTweetUrlTemplate + statusId;
                } else {
                    throw Error('An invalid status has been stored in local storage');
                }

                $http.post(actionUrl).success(function () {
                    cache.remove(statusId);
                }).error(function (data) {
                    if ($log !== undefined) {
                        $log.error(data)
                    }
                });
            });
            $scope.synced = true;
        }
    }
]);
