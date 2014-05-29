'use strict';

weaverApp.factory('twitter', ['$http', '$location', '$log', '$routeParams', '$timeout',
    function ($http, $location, $log, $routeParams, $timeout) {
        var initializeStatuses = function ($scope) {
            if ($scope.statuses === undefined) {
                $scope.statuses = [];
            }
        }

        var showStatuses = function ($scope, statuses) {
            initializeStatuses($scope);

            if (_.isUndefined($scope.statuses) || _.isEmpty($scope.statuses)) {
                $scope.statuses = statuses
            } else {
                _.each(statuses, function (status) {
                    $scope.statuses.unshift(status)
                });

                $timeout(function () {
                    $scope.$emit('ngRepeatDone');
                });
            }
        };

        return {
            showStatuses: showStatuses,
            showMoreStatuses: function ($scope, $routeParams) {
                var lastStatus;

                initializeStatuses($scope);

                if (!_.isEmpty($scope.statuses)) {
                    lastStatus = $scope.statuses[$scope.statuses.length - 1];
                    $routeParams.lastStatusId = lastStatus.id;
                }

                var host = $location.protocol() + '://' + $location.host(),
                    showLatestTweetsUrl = host + '/twitter/tweet/latest?username=' + $routeParams.username,
                    hash = '_' + $routeParams.username;

                if ($routeParams.lastStatusId !== undefined) {
                    showLatestTweetsUrl = showLatestTweetsUrl + '&lastStatusId=' + $routeParams.lastStatusId;
                    hash = hash + '_' + $routeParams.lastStatusId;
                }

                if ($scope.lockedRequests === undefined) {
                    $scope.lockedRequests = {};
                }

                if ($scope.lockedRequests[hash] === undefined) {
                    $scope.lockedRequests[hash] = true;

                    $http.get(showLatestTweetsUrl).success(function (statuses) {
                        if (_.isUndefined($scope.screenNames)) {
                            $scope.screenNames = {};
                        }

                        statuses.sort(function (a, b) {
                            return a['status_id'] > b['status_id'] ? 1 : -1;
                        });
                        statuses.reverse();

                        _.each(statuses, function (status) {
                            if (_.isUndefined($scope.screenNames[status.screen_name])) {
                                $scope.screenNames[status.screen_name] = {
                                    authorAvatar: status.author_avatar,
                                    screenName: status.screen_name,
                                    statuses: []
                                };
                            }

                            $scope.screenNames[status.screen_name].statuses.push(status);
                         });

                        showStatuses($scope, statuses);

                        $scope.lockedRequests[hash] = undefined;
                    }).error(function (data) {
                        if ($log !== undefined) {
                            $log.error(data)
                        }
                    });
                }
            },
            getBookmarksPromise: function (ids) {
                var host = $location.protocol() + '://' + $location.host(),
                    statusURL = host + '/twitter/bookmarks';

                return $http.post(statusURL, {
                    statusIds: ids,
                    username: $routeParams.username
                });
            }
        };
    }]
);