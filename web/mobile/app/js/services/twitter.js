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
                    lastStatus = $scope.statuses[0];
                    $routeParams.lastId = lastStatus.id;
                }

                var host = $location.protocol() + '://' + $location.host(),
                    showLatestTweetsUrl = host + '/twitter/tweet/latest?username=' + $routeParams.username,
                    hash = '_' + $routeParams.username;

                if ($routeParams.lastId !== undefined) {
                    showLatestTweetsUrl = showLatestTweetsUrl + '&lastId=' + $routeParams.lastId;
                    hash = hash + '_' + $routeParams.lastId;
                }

                if ($scope.lockedRequests === undefined) {
                    $scope.lockedRequests = {};
                }

                if ($scope.lockedRequests[hash] === undefined) {
                    $scope.lockedRequests[hash] = true;

                    $http.get(showLatestTweetsUrl).success(function (statuses) {
                        var firstRequest = false;

                        if (_.isUndefined($scope.screenNames)) {
                            $scope.screenNames = {};
                            firstRequest = true;
                        }

                        statuses.sort(function (a, b) {
                            return a['status_id'] > b['status_id'] ? 1 : -1;
                        });
                        statuses.reverse();

                        _.each($scope.screenNames, function (item) {
                            _.each(item.statuses, function (status) {
                                status.isNew = false;
                            });
                            item.isNew = false;
                        });

                        _.each(statuses, function (status) {
                            if (_.isUndefined($scope.screenNames[status.screen_name])) {
                                $scope.screenNames[status.screen_name] = {
                                    authorAvatar: status.author_avatar,
                                    isNew: false,
                                    screenName: status.screen_name,
                                    statuses: []
                                };
                            }

                            status.isNew = !firstRequest;
                            $scope.screenNames[status.screen_name].isNew = !firstRequest;
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