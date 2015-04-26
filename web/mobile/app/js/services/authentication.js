'use strict';

weaverApp.factory('oauthAuthentication', ['$q', '$routeParams', '$rootScope',
    function ($q, $routeParams, $rootScope) {
        return {
            request: function(config) {
                if (-1 !== config.url.indexOf('/api/twitter')) {
                    config.headers = config.headers || {};
                    if ($routeParams.oauth_token) {
                        config.headers.Authorization = 'Bearer ' + $routeParams.oauth_token;
                    }
                }

                return config || $q.when(config);
            },
            responseError: function(rejection) {
                if (rejection.status === 401) {
                    $rootScope.errors = ['Invalid access token'];
                }

                return $q.reject(rejection);
            }
        };
    }]
);
