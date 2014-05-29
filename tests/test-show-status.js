'use strict';

describe('Offline mode', function () {
    var $controller,
        $httpBackend,
        $routeParams,
        $scope,
        cache,
        httpBackend,
        locationMock,
        statusId,
        statuses;

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

        statusId = "420103690863669249";
        statuses = [
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
        $httpBackend.when('GET', 'https://## FILL HOSTNAME ##/twitter/tweet/latest?username=weaver').respond(statuses);

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

    it('should put statuses to scope', function () {
        httpBackend.flush();

        statuses[0].isNew = false;
        expect($scope.statuses).toEqual(statuses);
    });

    it('should let a status be starred', function () {
        $scope.star(statusId, 0);
        httpBackend.flush();
        expect(cache.get(statusId)).toEqual({starred: true});
    });

    it('should let a status be unstarred', function () {
        $scope.unstar(statusId, 0);
        httpBackend.flush();
        expect(cache.get(statusId)).toEqual({starred: false});
    });
});

describe('Posting statuses', function () {
    var $controller,
        $httpBackend,
        $log,
        $routeParams,
        $scope,
        cache,
        httpBackend,
        locationMock,
        screenName,
        statusId,
        statuses;

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

        statusId = "420103690863669249";
        screenName = "nikita_ppv";
        statuses = [
            {
                "author_avatar": "http://pbs.twimg.com/profile_images/1803355808/377992_203375376416531_100002322093627_443137_1695065966_n_normal.jpg",
                "text": "@schmittjoh Are those changes pushed to https://t.co/8X8XXLOSnB yet? Can't find anything in the recent commits.",
                "screen_name": screenName,
                "id": 4366498,
                "status_id": statusId,
                "starred": false
            }
        ];

        // Prepares http backend to respond with a tweet sample when requesting for the lastest ones
        $httpBackend = $injector.get('$httpBackend');
        httpBackend = $httpBackend;
        $httpBackend.when('GET', 'https://## FILL HOSTNAME ##/twitter/tweet/latest?username=weaver').respond(statuses);

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

    it('should update scope', function () {
        httpBackend.flush();

        statuses[0].isNew = false;
        expect($scope.statuses).toEqual(statuses);
    });

    it('should mark status as starred', function () {
        var endpoint = 'https://## FILL HOSTNAME ##/twitter/tweet/star/' + statusId;
        $httpBackend.when('POST', endpoint).respond({
            "status": statusId
        });
        $scope.star(statusId, screenName, 0);
        httpBackend.flush();

        expect($scope.statuses[0].starred).toEqual(true);
    });

    it('should mark status unstarred', function () {
        var endpoint = 'https://## FILL HOSTNAME ##/twitter/tweet/unstar/' + statusId;
        $httpBackend.when('POST', endpoint).respond({
            "status": statusId
        });
        $scope.unstar(statusId, screenName, 0);
        httpBackend.flush();

        expect($scope.statuses[0].starred).toEqual(false);
    });
});