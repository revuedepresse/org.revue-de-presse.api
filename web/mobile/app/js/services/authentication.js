'use strict';

weaverApp.factory('authentication', ['$q',
    function ($q) {
        return {
            'response': function (response) {
                if (!_.isUndefined(response.data) && !_.isUndefined(response.data.csrf_token)) {
                    return $q.reject({
                        data: {
                            info: 'Please authenticate first in order to use this application.'
                        },
                        status: 302,
                        headers: response.headers,
                        config: response.config
                    });
                }

                return response;
            }
        }
    }
]);