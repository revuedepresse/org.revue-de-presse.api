'use strict';

(function (require) {
    var weaverApp = require.weaverApp;

    weaverApp.factory('fallback', function () {
        return {
            isNavigatorOnline: function () {
                return navigator.onLine;
            }
        };
    });
})(this);
