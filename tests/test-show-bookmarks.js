'use strict';

describe('Show bookmarks', function () {
    var $controller,
        $routeParams,
        $scope,
        cache,
        httpBackend,
        locationMock,
        statusId,
        tweets;

    beforeEach(angular.mock.module('weaverApp'));

    $routeParams = { username: 'weaver' };
    beforeEach(angular.mock.module(function ($provide) {
        $provide.value('$routeParams', $routeParams);
    }));

    beforeEach(angular.mock.module(function ($provide) {
        var locationMockService = new LocationMockService();
        locationMock = locationMockService.getLocationMock();
        $provide.value('$location', locationMock);
    }));

    beforeEach(inject(function ($injector, $log, $angularCacheFactory, offlineCache, twitter) {
        var data,
            $rootScope;

        statusId = "420103690863669249";
        tweets = [
            {
                "author_avatar": "http://pbs.twimg.com/profile_images/1803355808/377992_203375376416531_100002322093627_443137_1695065966_n_normal.jpg",
                "text": "@schmittjoh Are those changes pushed to https://t.co/8X8XXLOSnB yet? Can't find anything in the recent commits.",
                "screen_name": "nikita_ppv",
                "id": 4366498,
                "status_id": statusId,
                "starred": false
            }
        ];

        // Prepares http backend to respond with a tweet sample when requesting for the lastest ones
        httpBackend = $injector.get('$httpBackend');

        data = {
            statusIds: [statusId],
            username: $routeParams.username
        };

        httpBackend.whenPOST('https://## FILL HOSTNAME ##/twitter/bookmarks', data).respond(tweets);

        $rootScope = $injector.get('$rootScope');
        $scope = $rootScope.$new();

        // Gets local storage cache to ensure items which have been starred are actually available in local storage
        cache = $angularCacheFactory.get('localStorageCache');
        if (cache === undefined) {
            cache = $angularCacheFactory('localStorageCache');
        }

        // Put status into storage cache
        cache.put(statusId, {starred: true});

        $controller = $injector.get('$controller');
        $controller('ShowBookmarksAction', {
            $scope: $scope,
            $log: $log,
            twitter: twitter,
            offlineCache: offlineCache
        });
    }));

    afterEach(function () {
        httpBackend.verifyNoOutstandingExpectation();
        httpBackend.verifyNoOutstandingRequest();
    });

    it('should have tweets in scope', function () {
        httpBackend.flush();
        expect($scope.tweets).toEqual(tweets);
    });
});