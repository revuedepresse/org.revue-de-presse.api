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
            var startTweetUrl = host + '/twitter/tweet/star/' + statusId;
            cache.put(statusId, {'starred': true});
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
            cache.put(statusId, {'starred': false});
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

twitterControllers.controller('SyncTweetsStarringStatusAction', [
    '$scope', '$http', '$location', '$angularCacheFactory',
    function ($scope, $http, $location, $angularCacheFactory) {
        var host = $location.protocol() + '://' + $location.host();
        var cache;

        cache = $angularCacheFactory.get('localStorageCache');
        if (cache === undefined) {
            cache = $angularCacheFactory('localStorageCache', {
                maxAge: 7*24*3600, // Items added to this cache expire after 1 week.
                cacheFlushInterval: 24*3600, // This cache will clear itself every day.
                deleteOnExpire: 'aggressive', // Items will be deleted from this cache right when they expire.
                storageMode: 'localStorage' // This cache will sync itself with `localStorage`.
            });
        }

        $scope.sync = function (statusId, index) {

        }
    }
]);
