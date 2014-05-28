'use strict';

describe('Starring tweets in offline mode', function () {
    var $controller,
        $httpBackend,
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

    beforeEach(angular.mock.module(function ($provide) {
        var fallback = jasmine.createSpyObj('fallback', ['isNavigatorOnline']);
        fallback.isNavigatorOnline.andCallFake(function () {
            return false;
        });
        $provide.value('fallback', fallback);
    }));

    beforeEach(inject(function ($injector, $angularCacheFactory, offlineCache) {
        var $rootScope;

        statusId = "420103690863669249"
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
        $httpBackend = $injector.get('$httpBackend');
        httpBackend = $httpBackend;
        $httpBackend.when('GET', 'https://## FILL HOSTNAME ##/twitter/tweet/latest?username=weaver').respond(tweets);

        $rootScope = $injector.get('$rootScope');
        $scope = $rootScope.$new();

        $controller = $injector.get('$controller');
        $controller('ShowStatusesAction', {
            $scope: $scope,
            $http: $injector.get('$http'),
            $location: locationMock,
            $log: $injector.get('$log'),
            $routeParams: $routeParams,
            twitter: $injector.get('twitter'),
            offlineCache: offlineCache
        });

        // Gets local storage cache to ensure items which have been starred are actually available in local storage
        cache = $angularCacheFactory.get('localStorageCache');
        if (cache === undefined) {
            cache = $angularCacheFactory('localStorageCache');
        }
    }));

    afterEach(function () {
        $httpBackend.verifyNoOutstandingExpectation();
        $httpBackend.verifyNoOutstandingRequest();
    });

    it('should have tweets in scope', function () {
        httpBackend.flush();
        expect($scope.tweets).toEqual(tweets);
    });

    it('should mark a tweet as starred', function () {
        $scope.star(statusId, 0);
        httpBackend.flush();
        expect(cache.get(statusId)).toEqual({starred: true});
    });

    it('should mark a tweet as being not starred', function () {
        $scope.unstar(statusId, 0);
        httpBackend.flush();
        expect(cache.get(statusId)).toEqual({starred: false});
    });
});

describe('Starring tweets', function () {
    var $controller,
        $httpBackend,
        $log,
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

    beforeEach(angular.mock.module(function ($provide) {
        var fallback = jasmine.createSpyObj('fallback', ['isNavigatorOnline']);
        fallback.isNavigatorOnline.andCallFake(function () {
            return true;
        });
        $provide.value('fallback', fallback);
    }));

    beforeEach(inject(function ($injector, $angularCacheFactory, offlineCache) {
        var $rootScope;

        statusId = "420103690863669249"
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
        $httpBackend = $injector.get('$httpBackend');
        httpBackend = $httpBackend;
        $httpBackend.when('GET', 'https://## FILL HOSTNAME ##/twitter/tweet/latest?username=weaver').respond(tweets);

        $rootScope = $injector.get('$rootScope');
        $scope = $rootScope.$new();

        $controller = $injector.get('$controller');
        $controller('ShowStatusesAction', {
            $scope: $scope,
            $http: $injector.get('$http'),
            $location: locationMock,
            $log: $injector.get('$log'),
            $routeParams: $routeParams,
            twitter: $injector.get('twitter'),
            offlineCache: offlineCache
        });

        // Gets local storage cache to ensure items which have been starred are actually available in local storage
        cache = $angularCacheFactory.get('localStorageCache');
        if (cache === undefined) {
            cache = $angularCacheFactory('localStorageCache');
        }
    }));

    afterEach(function () {
        $httpBackend.verifyNoOutstandingExpectation();
        $httpBackend.verifyNoOutstandingRequest();
    });

    it('should have tweets in scope', function () {
        httpBackend.flush();
        expect($scope.tweets).toEqual(tweets);
    });

    it('should mark a tweet as starred', function () {
        var endpoint = 'https://## FILL HOSTNAME ##/twitter/tweet/star/' + statusId;
        $httpBackend.when('POST', endpoint).respond({
            "status": statusId
        });
        $scope.star(statusId, 0);
        httpBackend.flush();

        expect($scope.tweets[0].starred).toEqual(true);
    });

    it('should mark a tweet as being not starred', function () {
        var endpoint = 'https://## FILL HOSTNAME ##/twitter/tweet/unstar/' + statusId;
        $httpBackend.when('POST', endpoint).respond({
            "status": statusId
        });
        $scope.unstar(statusId, 0);
        httpBackend.flush();

        expect($scope.tweets[0].starred).toEqual(false);
    });
});