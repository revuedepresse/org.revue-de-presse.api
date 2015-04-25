'use strict';

weaverApp.factory('oauthAuthentication', ['$window', '$q', '$location',
    function ($window, $q, $location) {

        var authorizeClient = function () {
            var clientId = '## FILL ME ##';
            var redirectUri = $location.protocol() + '://' + $location.host() + '/mobile/app.html';

            $window.location = '/oauth/v2/auth?client_id=' + clientId +
                '&response_type=code' +
                '&redirect_uri=' + encodeURIComponent(redirectUri)
            ;
        };

        return {
            request: function(config) {
                config.headers = config.headers || {};
                if ($window.oauthToken) {
                    config.headers.Authorization = 'Bearer ' + $window.oauthToken;
                } else {
                    authorizeClient();
                }

                return config || $q.when(config);
            },
            response: function(response) {
                return response || $q.when(response);
            }
        };
    }]
);
