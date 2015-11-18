'use strict';

(function (require) {
    var weaverApp = require.weaverApp;

    weaverApp.factory('twitter', [
        '$http', '$location', '$log', '$routeParams', '$timeout',
        function ($http, $location, $log, $routeParams, $timeout) {
            var initializeStatuses = function ($scope) {
                if (_.isUndefined($scope.statuses)) {
                    $scope.statuses = [];
                }
            };

            var orderBy = function (field) {
                return function (a, b) {
                    if (a === b) {
                        return 0;
                    } else {
                        return a[field] < b[field] ? -1 : 1;
                    }
                };
            };

            var showStatuses = function ($scope, statuses) {
                if (_.isEmpty($scope.statuses)) {
                    $scope.statuses = statuses.sort(orderBy('id')).reverse();
                } else {
                    _.each(statuses, function (status) {
                        $scope.statuses.unshift(status);
                    });

                    $scope.statuses = statuses.sort(orderBy('id')).reverse();

                    $timeout(function () {
                        $scope.$emit('ngRepeatDone');
                    });
                }
            };

            var deprecateNovelty = function ($scope) {
                _.each($scope.users, function (item) {
                    _.each(item.sortedStatuses, function (status, index) {
                        item.sortedStatuses[index].isNew = false;
                    });
                    item.isNew = false;
                });
            };

            var getHost = function ($location) {
                return $location.protocol() + '://' + $location.host();
            };

            var getBookmarksPromise = function (ids) {
                var statusURL = getHost($location) + '/api/twitter/bookmarks';

                return $http.post(statusURL, {
                    statusIds: ids,
                    username: $routeParams.username
                });
            };

            var initializeRequestLocking = function ($scope) {
                if (_.isUndefined($scope.lockedRequests)) {
                    $scope.lockedRequests = {};
                }
            };

            var latestStatusesEndpoint = function ($location, $scope, $routeParams) {
                var endpoint = getHost($location) + '/api/twitter/tweet/latest',
                    params = getLatestStatusesParams($routeParams, $scope);

                _.each(params, function (param, index) {
                    var separator;

                    if (index === 0) {
                        separator = '?';
                    } else {
                        separator = '&';
                    }

                    endpoint = endpoint + separator + param;
                });

                return endpoint;
            };

            var setUserParam = function (user) {
                return 'username=' + user;
            };

            var initializeHash = function (username) {
                return '_' + username;
            };

            var getOldestStatusId = function ($scope) {
                var oldestStatus;

                if (!_.isEmpty($scope.statuses)) {
                    oldestStatus = $scope.statuses[$scope.statuses.length - 1];

                    return oldestStatus.id;
                } else {
                    return undefined;
                }
            };

            var getLatestStatusesParams = function ($routeParams, $scope) {
                var params = [],
                    lastId = getOldestStatusId($scope);

                params.push(setUserParam($routeParams.username));
                if (!_.isUndefined(lastId)) {
                    params.push('lastId=' + lastId);
                }

                return params;
            };

            var hashRequestParams = function ($routeParams, $scope) {
                var hash = initializeHash($routeParams.username);
                if (!_.isUndefined(getOldestStatusId($scope))) {
                    hash = hash + '_' + getOldestStatusId($scope);
                }

                return hash;
            };

            var isFirstRequest = function ($scope, next) {
                var firstRequest = false;
                if (_.isUndefined($scope.users)) {
                    firstRequest = true;
                }

                next($scope);

                return firstRequest;
            };

            var initializeUsers = function ($scope) {
                if (_.isUndefined($scope.users)) {
                    $scope.users = {};
                }
            };

            var sortUserStatuses = function ($scope) {
                _.each($scope.users, function (item, screenName) {
                    item.sortedStatuses.sort(orderBy('id'));
                    item.sortedStatuses.reverse();

                    $scope.users[screenName].sortedStatuses = item.sortedStatuses;
                });
            };

            var unlockRequest = function ($scope, hash) {
                $scope.lockedRequests[hash] = undefined;
            };

            var lockRequest = function ($scope, hash) {
                $scope.lockedRequests[hash] = true;
            };

            var isRequestLocked = function ($scope, hash) {
                return _.isUndefined($scope.lockedRequests[hash]);
            };

            var declareNovelty = function (item, isNew) {
                item.isNew = isNew;
            };

            var firstSeenStatus = function ($scope, status) {
                return _.isUndefined($scope.users[status.screen_name].statuses[status.status_id]);
            };

            var firstSeenUser = function ($scope, status) {
                return _.isUndefined($scope.users[status.screen_name]);
            };

            var injectStatus = function ($scope, status) {
                $scope.users[status.screen_name].statuses[status.status_id] = status;
                $scope.users[status.screen_name].sortedStatuses.push(status);
            };

            var showMoreStatuses = function ($scope, $routeParams) {
                var endpoint,
                    hash = hashRequestParams($routeParams, $scope);

                initializeStatuses($scope);

                endpoint = latestStatusesEndpoint($location, $scope, $routeParams);

                initializeRequestLocking($scope);

                if (isRequestLocked($scope, hash)) {
                    lockRequest($scope, hash);

                    $http.get(endpoint).success(function (data) {
                        var firstRequest = isFirstRequest($scope, initializeUsers);

                        deprecateNovelty($scope);

                        _.each(data, function (status, index) {
                            if (firstSeenUser($scope, status)) {
                                $scope.users[status.screen_name] = {
                                    authorAvatar: status.author_avatar,
                                    isNew: false,
                                    screenName: status.screen_name,
                                    statuses: {},
                                    sortedStatuses: []
                                };
                            }

                            declareNovelty(data[index], !firstRequest);
                            declareNovelty($scope.users[status.screen_name], !firstRequest);

                            if (firstSeenStatus($scope, status)) {
                                injectStatus($scope, status);
                            }
                        });

                        sortUserStatuses($scope);

                        showStatuses($scope, data);

                        unlockRequest($scope, hash);
                    }).error(function (data) {
                        if (!_.isUndefined(data.info)) {
                            if (_.isUndefined($scope.errors)) {
                                $scope.errors = [];
                            }

                            $scope.errors.push(data.info);
                        } else if ($log !== undefined) {
                            $log.info(data);
                        }
                    });
                }
            };

            return {
                getBookmarksPromise: getBookmarksPromise,
                showStatuses: showStatuses,
                showMoreStatuses: showMoreStatuses
            };
        }
    ]);
})(this);
