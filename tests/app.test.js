'use strict';

describe('ShowTweetsAction', function () {
   var scope, httpBackend, locationMock, tweets,
       $controller, $httpBackend, $log, $rootScope;

   beforeEach(angular.mock.module('weaverApp'));
   beforeEach(inject(function ($injector, twitter) {
       tweets = [
           {
               "author_avatar": "http://pbs.twimg.com/profile_images/1803355808/377992_203375376416531_100002322093627_443137_1695065966_n_normal.jpg",
               "text": "@schmittjoh Are those changes pushed to https://t.co/8X8XXLOSnB yet? Can't find anything in the recent commits.",
               "screen_name": "nikita_ppv",
               "id": 4366498,
               "status_id": "420103690863669249",
               "starred": true
           }
       ];
       $httpBackend = $injector.get('$httpBackend');
       httpBackend = $httpBackend;
       $httpBackend.when('GET', 'https://## FILL HOSTNAME ##/twitter/tweet/latest?username=weaver').respond(tweets);

       locationMock = jasmine.createSpyObj('location', ['protocol', 'host']);
       locationMock.$host = '## FILL HOSTNAME ##';
       locationMock.$protocol = 'https';
       locationMock.host.andCallFake(function() {
           return this.$host;
       });
       locationMock.protocol.andCallFake(function() {
           return this.$protocol;
       });

       $rootScope = $injector.get('$rootScope');
       scope = $rootScope.$new();

       $controller = $injector.get('$controller');
       $log = $injector.get('$log');

       $controller('ShowTweetsAction', {
           $scope: scope,
           $http: $injector.get('$http'),
           $location: locationMock,
           $routeParams: {username: 'weaver'},
           $log: $log,
           twitter: twitter
       });
   }));

   afterEach(function() {
       $httpBackend.verifyNoOutstandingExpectation();
       $httpBackend.verifyNoOutstandingRequest();
   });

   it('should have tweets', function () {
       httpBackend.flush();
       expect(scope.tweets).toEqual(tweets);
   });
});