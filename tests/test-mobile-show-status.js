'use strict';

var statusIds = [
        "420103690863669249",
        "420103690863669248",
        "420103690863669247",
        "420103690863669246",
        "420103690863669245",
        "420103690863669244"
    ],
    verbose = 'DEBUG';

describe('Accessing statuses', function () {
    var $controller,
        $httpBackend,
        $routeParams,
        $scope,
        cache,
        ids = [6, 5, 4, 3, 2, 1],
        locationMock,
        screenName,
        statuses;

    var responseGetter = function (endpoint, content, log) {
            var logExpectation = log || false;

            if (logExpectation) {
                console.log('expecting GET ' + endpoint);
            }

            return function (method, url) {
                if (verbose === 'DEBUG' && logExpectation) {
                    console.log(method + ' ' + url);
                }

                return [200, content, {}]
            }
        };

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
        fallback.isNavigatorOnline = jasmine.createSpy().and.returnValue(false);
        $provide.value('fallback', fallback);
    }));

    beforeEach(inject(function ($injector, $angularCacheFactory, offlineCache) {
        var endpoint,
            $rootScope;

        screenName = "nikita_ppv";
        statuses = [
            {
                "author_avatar": "http://example.com/avatar2.jpg",
                "text": "third tweet which has been published",
                "screen_name": screenName,
                "id": ids[2],
                "status_id": statusIds[2],
                "starred": false
            }, {
                "author_avatar": "http://pbs.twimg.com/profile_images/1803355808/377992_203375376416531_100002322093627_443137_1695065966_n_normal.jpg",
                "text": "first tweet which has been published",
                "screen_name": screenName,
                "id": ids[0],
                "status_id": statusIds[0],
                "starred": false
            }, {
                "author_avatar": "http://example.com/avatar2.jpg",
                "text": "second tweet which has been published",
                "screen_name": screenName,
                "id": ids[1],
                "status_id": statusIds[1],
                "starred": false
            }
        ];

        // Prepares http backend to respond with a tweet sample when requesting for the lastest ones
        $httpBackend = $injector.get('$httpBackend');

        endpoint = 'https://## FILL HOSTNAME ##/api/twitter/tweet/latest?username=weaver';
        $httpBackend.when('GET', endpoint).respond(responseGetter(endpoint, statuses));

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

    it('should add statuses to the scope after sorting them from the newest one to the oldest one', function () {
        var firstStatus  = statuses[1],
            secondStatus = statuses[2],
            thirdStatus  = statuses[0];

        $httpBackend.flush();

        firstStatus.isNew  = false;
        secondStatus.isNew = false;
        thirdStatus.isNew  = false;

        var sortedStatuses = [firstStatus, secondStatus, thirdStatus];

        expect($scope.statuses).toEqual(sortedStatuses);
    });

    it('Sorting statuses', function () {
        var status = statuses[0];

        $httpBackend.flush();

        expect($scope.users).toBeDefined();
        expect($scope.users[status.screen_name]).toBeDefined();

        expect($scope.users[status.screen_name].isNew).toEqual(false);
        expect($scope.users[status.screen_name].statuses[status.status_id]).toBeDefined();
    });

    describe('In offline mode', function () {
        it('should put a status into cache when it has been starred', function () {
            $scope.star(statusId, screenName);
            $httpBackend.flush();
            expect(cache.get(statusId)).toEqual({starred: true});
        });

        it('should put a status into cache when it has been unstarred', function () {
            $scope.unstar(statusId, screenName);
            $httpBackend.flush();
            expect(cache.get(statusId)).toEqual({starred: false});
        });
    });

    describe('Showing more statuses', function () {
        beforeEach(function () {
            var endpoint,
                olderStatuses;

            olderStatuses = [
                {
                    "author_avatar": "http://example.com/avatar.jpg",
                    "text": "Older tweet.",
                    "screen_name": "nikita_ppv",
                    "id": ids[3],
                    "status_id": statusIds[3],
                    "starred": false
                }
            ];

            endpoint = 'https://## FILL HOSTNAME ##/api/twitter/tweet/latest?username=weaver&lastId=' + ids[2];
            $httpBackend.when('GET', endpoint).respond(responseGetter(endpoint, olderStatuses));
        });

        it('should highlight a user for whom more tweets have been published.', function () {
            var status = statuses[0],
                lastStatusIndex;

            // Responds with statuses
            $httpBackend.flush();

            // It should sort statuses from newest to oldest
            $scope.showMoreStatuses();

            // Responds with more statuses
            $httpBackend.flush();

            expect($scope.users[status.screen_name].isNew).toEqual(true);
            expect($scope.users[status.screen_name].sortedStatuses[0].id).toEqual(ids[0]);

            lastStatusIndex = $scope.users[status.screen_name].sortedStatuses.length - 1;
            expect($scope.users[status.screen_name].sortedStatuses[lastStatusIndex].id).toEqual(ids[3]);
        });

        describe('Showing even more statuses belonging to other users', function () {
            var otherUserScreenName = 'other_user';

            beforeEach(function () {
                var endpoint,
                    olderStatuses;

                olderStatuses = [
                    {
                        "author_avatar": "http://example.com/avatar.jpg",
                        "text": "Older tweet.",
                        "screen_name": otherUserScreenName,
                        "id": ids[4],
                        "status_id": statusIds[4],
                        "starred": false
                    }
                ];

                endpoint = 'https://## FILL HOSTNAME ##/api/twitter/tweet/latest?username=weaver&lastId=' + ids[3];
                $httpBackend.when('GET', endpoint).respond(responseGetter(endpoint, olderStatuses));
            });

            it('should highlight a user for whom more tweets have been published.', function () {
                var status = statuses[0];

                // Responds with statuses
                $httpBackend.flush();

                $scope.showMoreStatuses();

                // Responds with more statuses
                $httpBackend.flush();

                $scope.showMoreStatuses();

                // Responds with more statuses belonging to another user than the first who provided statuses
                $httpBackend.flush();

                expect($scope.users[status.screen_name].isNew).toEqual(false);
                expect($scope.users[otherUserScreenName].isNew).toEqual(true);
            });
        });
    });
});

describe('Pressing a favorite button', function () {
    var $controller,
        $httpBackend,
        $log,
        $routeParams,
        $scope,
        cache,
        locationMock,
        screenName,
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
        fallback.isNavigatorOnline = jasmine.createSpy().and.returnValue(true);
        $provide.value('fallback', fallback);
    }));

    beforeEach(inject(function ($injector, $angularCacheFactory, offlineCache) {
        var $rootScope;

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
        $httpBackend.when('GET', 'https://## FILL HOSTNAME ##/api/twitter/tweet/latest?username=weaver').respond(statuses);

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

    it('should add statuses to the scope', function () {
        $httpBackend.flush();

        statuses[0].isNew = false;
        expect($scope.statuses).toEqual(statuses);
    });

    it('should mark statuses in the scope as starred', function () {
        var endpoint = 'https://## FILL HOSTNAME ##/api/twitter/tweet/star/' + statusId;
        $httpBackend.when('POST', endpoint).respond({
            "status": statusId
        });
        $scope.star(statusId, screenName, 0);
        $httpBackend.flush();

        expect($scope.statuses[0].starred).toEqual(true);
    });

    it('should mark statuses in the scope as unstarred', function () {
        var endpoint = 'https://## FILL HOSTNAME ##/api/twitter/tweet/unstar/' + statusId;
        $httpBackend.when('POST', endpoint).respond({
            "status": statusId
        });
        $scope.unstar(statusId, screenName, 0);
        $httpBackend.flush();

        expect($scope.statuses[0].starred).toEqual(false);
    });
});