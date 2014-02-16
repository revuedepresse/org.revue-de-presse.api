'use strict';

describe('SyncTweetsStarringStatusAction', function () {
    var $controller,
        $httpBackend,
        $scope,
        cache,
        httpBackend,
        locationMock,
        statusId;
    var locationMockService = new LocationMockService();

    beforeEach(angular.mock.module('weaverApp'));
    beforeEach(inject(function ($injector, offlineCache) {
        var $rootScope;

        locationMock = locationMockService.getLocationMock();

        $rootScope = $injector.get('$rootScope');
        $scope = $rootScope.$new();

        // Emulates previous use of offline cache service to store the result of a starring action
        statusId = "420103690863669249"
        cache = offlineCache.getLocalStorageCache();

        $httpBackend = $injector.get('$httpBackend');
        httpBackend = $httpBackend;

        $controller = $injector.get('$controller');
        $controller('SyncTweetsStarringStatusAction', {
            $scope: $scope,
            $http: $injector.get('$http'),
            $location: locationMock,
            offlineCache: offlineCache
        });
    }));

    afterEach(function () {
        $scope.sync();
        httpBackend.flush();

        expect(cache.keys().length).toEqual(0);
        expect($scope.synced).toEqual(true);

        $httpBackend.verifyNoOutstandingExpectation();
        $httpBackend.verifyNoOutstandingRequest();
    });

    it('should sync tweets', function () {
        var endpoint = 'https://## FILL HOSTNAME ##/twitter/tweet/star/' + statusId;
        $httpBackend.when('POST', endpoint).respond({
            "status": statusId
        });
        cache.put(statusId, {'starred': true});
    });

    it('should sync tweets', function () {
        var endpoint = 'https://## FILL HOSTNAME ##/twitter/tweet/unstar/' + statusId;
        $httpBackend.when('POST', endpoint).respond({
            "status": statusId
        });
        cache.put(statusId, {'starred': false});
    });
});