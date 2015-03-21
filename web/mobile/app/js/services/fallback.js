'use strict';

weaverApp.factory('fallback', function () {
        return {
            isNavigatorOnline: function () {
                return navigator.onLine;
            }
        };
    }
);