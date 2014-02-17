'use strict';

/* Controllers */

var twitterControllers = angular.module('twitterControllers', []);

twitterControllers.controller('ShowTweetsAction', [
    '$scope', '$http', '$location', '$log', '$routeParams', 'twitter', 'offlineCache',
    function ($scope, $http, $location, $log, $routeParams, twitter, offlineCache) {
        var cache = offlineCache.getLocalStorageCache(),
            host = $location.protocol() + '://' + $location.host();

        twitter.showMoreTweets($scope, $routeParams);

        $scope.star = function (statusId, index) {
            var starTweetUrl = host + '/twitter/tweet/star/' + statusId;

            if (offlineCache.isNavigatorOnline()) {
                $http.post(starTweetUrl).success(function () {
                    $scope.tweets[index].starred = true;
                }).error(function (data) {
                    if ($log !== undefined) {
                        $log.error(data)
                    }
                });
            } else {
                cache.put(statusId, {'starred': true});
                $scope.synced = false;
            }
        }

        $scope.unstar = function (statusId, index) {
            var unstarTweetUrl = host + '/twitter/tweet/unstar/' + statusId;

            if (offlineCache.isNavigatorOnline()) {
                $http.post(unstarTweetUrl).success(function () {
                    $scope.tweets[index].starred = false;
                }).error(function (data) {
                    if ($log !== undefined) {
                        $log.error(data)
                    }
                });
            } else {
                cache.put(statusId, {'starred': false});
                $scope.synced = false;
            }
        }
    }
]);

twitterControllers.controller('SyncTweetsStarringStatusAction', [
    '$scope', '$http', '$location', '$log', 'offlineCache',
    function ($scope, $http, $location, $log, offlineCache) {
        var cache = offlineCache.getLocalStorageCache(),
            errors = {};

        $scope.synced = cache.keys().length === 0;

        $scope.sync = function () {
            var host = $location.protocol() + '://' + $location.host(),
                keys = cache.keys(),

                unstarTweetUrlTemplate = host + '/twitter/tweet/unstar/',
                starTweetUrlTemplate = host + '/twitter/tweet/star/';

            angular.forEach(keys, function (statusId) {
                var actionUrl,
                    tweetStatus = cache.get(statusId);

                if (tweetStatus.starred) {
                    actionUrl = starTweetUrlTemplate + statusId;
                } else if (!tweetStatus.starred) {
                    actionUrl = unstarTweetUrlTemplate + statusId;
                } else {
                    throw Error('An invalid status has been stored in local storage');
                }

                if (offlineCache.isNavigatorOnline()) {
                    if (errors.brokenInternet !== undefined) {
                        errors.brokenInternet = undefined;
                    }
                    $http.post(actionUrl).success(function () {
                        cache.remove(statusId);
                        errors.actionUrl = undefined;
                    }).error(function (data) {
                        errors.actionUrl = data;
                    });
                } else {
                    var brokenInternet = 'Oops, the Internet seems to be broken :/';
                    if (errors.brokenInternet === undefined) {
                        errors.brokenInternet = brokenInternet;
                    }
                }
            });

            if (Object.keys(errors).length > 0) {
                $scope.errors = errors;
            } else {
                $scope.synced = true;
                $scope.errors = undefined;
            }
        }
    }
]);
