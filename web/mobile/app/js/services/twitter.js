'use strict';

weaverApp.factory('twitter', ['$http', '$location', '$log',
    function ($http, $location, $log) {
        return {
            showMoreTweets: function ($scope, $routeParams) {
                var host = $location.protocol() + '://' + $location.host();
                var showLatestTweetsUrl = host + '/twitter/tweet/latest?username=' + $routeParams.username;

                $http.get(showLatestTweetsUrl).success(function (tweets) {
                    if (undefined === $scope.tweets) {
                        $scope.tweets = tweets
                    } else {
                        var k;
                        for (k = 0; k < 50; k++) {
                            $scope.tweets.push(tweets[k])
                        }
                    }
                }).error(function (data) {
                    if ($log !== undefined) {
                        $log.error(data)
                    }
                });
           }
        };
    }]
);