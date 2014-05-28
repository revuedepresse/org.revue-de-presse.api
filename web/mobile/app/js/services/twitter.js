'use strict';

weaverApp.factory('twitter', ['$http', '$location', '$log', '$routeParams',
    function ($http, $location, $log, $routeParams) {
        var showStatuses = function ($scope, tweets) {
            if (undefined === $scope.tweets) {
                $scope.tweets = tweets
            } else {
                var k;
                for (k = 0; k < 50; k++) {
                    $scope.tweets.push(tweets[k])
                }
            }
        };

        return {
            showStatuses: showStatuses,
            showMoreTweets: function ($scope, $routeParams) {
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

                    $http.get(showLatestTweetsUrl).success(function (tweets) {
                        showStatuses($scope, tweets);

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