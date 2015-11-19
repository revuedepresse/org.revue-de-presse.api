'use strict';

(function (require) {

    // Emulates previous use of offline cache service to store the result of a starring action
    var statusId = '420103690863669249';
    var angular = require.angular;
    var inject = require.inject;
    var LocationMockService = require.LocationMockService;

    describe('Syncing tweets status', function () {
        var $controller,
            $httpBackend,
            $routeParams,
            $scope,
            cache,
            httpBackend,
            locationMock;

        beforeEach(angular.mock.module('weaverApp'));

        $routeParams = {username: 'weaver'};
        beforeEach(angular.mock.module(function ($provide) {
            var fallback = jasmine.createSpyObj('fallback', ['isNavigatorOnline']);
            fallback.isNavigatorOnline = jasmine.createSpy().and.returnValue(false);
            $provide.value('fallback', fallback);
        }));

        beforeEach(angular.mock.module(function ($provide) {
            var locationMockService = new LocationMockService();
            locationMock = locationMockService.getLocationMock();
            $provide.value('$location', locationMock);
        }));

        beforeEach(inject(function ($injector, offlineCache) {
            var $rootScope;
            var tweets = [{
                'author_avatar': 'http://pbs.twimg.com/profile_images/' +
                '1803355808/377992_203375376416531_100002322093627_443137_1695065966_n_normal.jpg',
                'text': '@schmittjoh Are those changes pushed to ' +
                'https://t.co/8X8XXLOSnB yet? Can\'t find anything in the recent commits.',
                'screen_name': 'nikita_ppv',
                'id': 4366498,
                'status_id': statusId,
                'starred': false
            }];

            $rootScope = $injector.get('$rootScope');
            $scope = $rootScope.$new();

            cache = offlineCache.getLocalStorageCache();

            $httpBackend = $injector.get('$httpBackend');
            httpBackend = $httpBackend;

            $httpBackend.when('GET', 'https://## FILL HOSTNAME ##/api/twitter/tweet/latest?username=weaver').respond(tweets);

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
        }));

        afterEach(function () {
            $scope.sync();

            expect(cache.keys().length).toEqual(1);
            expect(Object.keys($scope.errors).length > 0).toEqual(true);
            expect(typeof $scope.errors.brokenInternet).toEqual('string');

            $httpBackend.verifyNoOutstandingExpectation();
            $httpBackend.verifyNoOutstandingRequest();
        });

        describe('In offline mode', function () {
            it('should sync tweets marked as starred', function () {
                httpBackend.flush();

                cache.put(statusId, {'starred': true});
            });

            it('should sync tweets not marked as starred', function () {
                httpBackend.flush();

                cache.put(statusId, {'starred': false});
            });
        });
    });

    describe('Syncing tweet status', function () {
        var $controller,
            $httpBackend,
            $routeParams,
            $scope,
            cache,
            httpBackend,
            locationMock;

        $routeParams = {username: 'weaver'};
        beforeEach(angular.mock.module('weaverApp'));

        beforeEach(angular.mock.module(function ($provide) {
            var fallback = jasmine.createSpyObj('fallback', ['isNavigatorOnline']);
            fallback.isNavigatorOnline = jasmine.createSpy().and.returnValue(true);
            $provide.value('fallback', fallback);
        }));

        beforeEach(angular.mock.module(function ($provide) {
            var locationMockService = new LocationMockService();
            locationMock = locationMockService.getLocationMock();
            $provide.value('$location', locationMock);
        }));

        beforeEach(inject(function ($injector, offlineCache) {
            var $rootScope;

            $rootScope = $injector.get('$rootScope');
            $scope = $rootScope.$new();

            // Emulates previous use of offline cache service to store the result of a starring action
            cache = offlineCache.getLocalStorageCache();

            $httpBackend = $injector.get('$httpBackend');
            httpBackend = $httpBackend;

            var tweets = [
                {
                    'author_avatar': 'http://pbs.twimg.com/profile_images/' +
                    '1803355808/377992_203375376416531_100002322093627_443137_1695065966_n_normal.jpg',
                    'text': '@schmittjoh Are those changes pushed to ' +
                    'https://t.co/8X8XXLOSnB yet? Can\'t find anything in the recent commits.',
                    'screen_name': 'nikita_ppv',
                    'id': 4366498,
                    'status_id': statusId,
                    'starred': false
                }
            ];
            $httpBackend.when('GET', 'https://## FILL HOSTNAME ##/api/twitter/tweet/latest?username=weaver').respond(tweets);

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
        }));

        afterEach(function () {
            $scope.sync();
            httpBackend.flush();

            expect(cache.keys().length).toEqual(0);
            expect($scope.errors).toEqual(undefined);

            $httpBackend.verifyNoOutstandingExpectation();
            $httpBackend.verifyNoOutstandingRequest();
        });

        it('should sync tweets marked as starred', function () {
            httpBackend.flush();

            var endpoint = 'https://## FILL HOSTNAME ##/api/twitter/tweet/star/' + statusId;
            $httpBackend.when('POST', endpoint).respond({
                'status': statusId
            });
            cache.put(statusId, {'starred': true});
        });

        it('should sync tweets not marked as starred', function () {
            httpBackend.flush();

            var endpoint = 'https://## FILL HOSTNAME ##/api/twitter/tweet/unstar/' + statusId;
            $httpBackend.when('POST', endpoint).respond({
                'status': statusId
            });
            cache.put(statusId, {'starred': false});
        });
    });
})(window);
