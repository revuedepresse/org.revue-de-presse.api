'use strict';

var twitterControllers = angular.module('bookmarkController', []);

twitterControllers.controller('ShowBookmarksAction', [
    '$scope', '$log', 'offlineCache', 'twitter',
    function ($scope, $log, offlineCache, twitter) {
        var cache = offlineCache.getLocalStorageCache(),
            statusIds = cache.keys();

        twitter.getBookmarksPromise(statusIds)
        .success(function (tweets) {
            twitter.showStatuses($scope, tweets);
        }).error(function (data) {
            if ($log !== undefined) {
                $log.error(data)
            }
        });
    }
]);