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

weaverApp.factory('offlineCache', ['$angularCacheFactory',
    function (cacheFactory) {
        return {
            getLocalStorageCache: function () {
                var cache = cacheFactory.get('localStorageCache');
                if (cache === undefined) {
                    cache = cacheFactory('localStorageCache', {
                        maxAge: 7 * 24 * 3600, // Items added to this cache expire after 1 week.
                        cacheFlushInterval: 24 * 3600, // This cache will clear itself every day.
                        deleteOnExpire: 'aggressive', // Items will be deleted from this cache right when they expire.
                        storageMode: 'localStorage' // This cache will sync itself with `localStorage`.
                    });
                }

                return cache;
            }
        };
    }]
);