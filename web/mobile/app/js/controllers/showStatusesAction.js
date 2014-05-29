'use strict';

var debug = false,
    verbose = false,
    performance = false,
    twitterControllers = angular.module('statusController', []);

twitterControllers.controller('ShowStatusesAction', [
    '$scope', '$http', '$location', '$log', '$routeParams', 'twitter', 'offlineCache',
    function ($scope, $http, $location, $log, $routeParams, twitter, offlineCache) {
        var cache = offlineCache.getLocalStorageCache(),
            host = $location.protocol() + '://' + $location.host(),
            errors = {};

        $scope.synced = cache.keys().length === 0;

        $scope.$on('ngRepeatDone', function () {
            $('.ui.accordion')
                .accordion({
                    'collapse': true,
                    'exclusive': false,
                    'debug': debug,
                    'performance': performance,
                    'verbose': verbose
                })
            ;
        });

        twitter.showMoreStatuses($scope, $routeParams);

        var setTweetStarringStatus = function (statusId, screenName, index, starred) {
            if (offlineCache.isNavigatorOnline()) {
                var endpointUrlTemplate = host + '/twitter/tweet/{{ action }}/' + statusId;
                var endpointUrl;

                if (starred) {
                    endpointUrl = endpointUrlTemplate.replace('{{ action }}', 'star');
                } else {
                    endpointUrl = endpointUrlTemplate.replace('{{ action }}', 'unstar');
                }

                $http.post(endpointUrl).success(function () {
                    $scope.screenNames[screenName].statuses[index].starred = starred;
                }).error(function (data) {
                    if ($log !== undefined) {
                        $log.error(data)
                    }
                });
            } else {
                cache.put(statusId, {'starred': starred});
                $scope.synced = false;
                $scope.$apply();
            }
        }

        $scope.star = function (tweetId, screenName, tweetIndex) {
            setTweetStarringStatus(tweetId, screenName, tweetIndex, true);
        }

        $scope.unstar = function (tweetId, screenName, tweetIndex) {
            setTweetStarringStatus(tweetId, screenName, tweetIndex, false);
        }

        $scope.showMoreStatuses = function () {
            twitter.showMoreStatuses($scope, $routeParams);
        }

        $scope.sync = function () {
            var host = $location.protocol() + '://' + $location.host(),
                keys = cache.keys(),
                unstarTweetUrlTemplate = host + '/twitter/tweet/unstar/',
                starTweetUrlTemplate = host + '/twitter/tweet/star/';

            var updateSyncStatus = function () {
                if (Object.keys(errors).length > 0) {
                    $scope.errors = errors;
                } else {
                    $scope.synced = true;
                    $scope.errors = undefined;
                }
            }

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
                    $http.post(actionUrl).success(function () {
                        cache.remove(statusId);

                        if (errors.actionUrl === actionUrl) {
                            delete errors.actionUrl;
                        }
                        if (errors.brokenInternet !== undefined) {
                            delete errors.brokenInternet;
                        }
                    }).error(function () {
                        errors.failedAction = 'Failed to access "{{ action }}"'.replace(
                            '{{ action }}', actionUrl
                        );
                    }).then(updateSyncStatus);
                } else {
                    var brokenInternet = 'Oops, the Internet seems to be broken :/';
                    if (errors.brokenInternet === undefined) {
                        errors.brokenInternet = brokenInternet;
                    }
                }
            });

            updateSyncStatus();
        }
    }
]);