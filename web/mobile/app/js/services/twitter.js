'use strict';

weaverApp.factory('twitter', ['$http', '$location', '$log',
    function ($http, $location, $log) {
        return {
            showMoreTweets: function ($scope, $routeParams) {
                var host = $location.protocol() + '://' + $location.host(),
                    showLatestTweetsUrl = host + '/twitter/tweet/latest?username=' + $routeParams.username,
                    hash = '_' + $routeParams.username

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
                        if (undefined === $scope.tweets) {
                            $scope.tweets = tweets
                        } else {
                            var k;
                            for (k = 0; k < 50; k++) {
                                $scope.tweets.push(tweets[k])
                            }
                        }

                        $scope.lockedRequests[hash] = undefined;
                    }).error(function (data) {
                        if ($log !== undefined) {
                            $log.error(data)
                        }
                    });
                }
           }
        };
    }]
);