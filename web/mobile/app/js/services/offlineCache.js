'use strict';

weaverApp.factory('offlineCache', ['$angularCacheFactory', 'fallback',
    function (cacheFactory, fallback) {
        return {
            getLocalStorageCache: function () {
                var cache = cacheFactory.get('localStorageCache');
                if (cache === undefined) {
                    cache = cacheFactory('localStorageCache', {
                        maxAge: 7 * 24 * 3600, // Items added to this cache expire after 1 week.
                        cacheFlushInterval: 24 * 3600, // This cache will clear itself every day.
                        deleteOnExpire: 'aggressive', // Items will be deleted from this cache right when they expire.
                        storageMode: 'localStorage' // This cache will sync itself with `localStorage`.
                    });
                }

                return cache;
            },
            isNavigatorOnline: function () {
                return fallback.isNavigatorOnline();
            }
        };
    }]
);