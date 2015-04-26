'use strict';

weaverApp.factory('oauthAuthentication', function ($window, $q) {
    return {
        request: function(config) {
            config.headers = config.headers || {};
            if ($window.oauthToken) {
                config.headers.Authorization = 'Bearer ' + $window.oauthToken;
            }

            return config || $q.when(config);
        },
        response: function(response) {
            if (response.status === 401) {
                $window.location = '/';
            }
            return response || $q.when(response);
        }
    };
});