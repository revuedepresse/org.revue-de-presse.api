'use strict';

(function (require) {
    var getQueryParams = require.getQueryParams;

    describe('Query params getter', function () {
        var params;

        beforeEach(function () {
            params = '?param=value';
        });

        it('should get query params from the location bar', function () {
            var queryParams = getQueryParams(params);
            expect(queryParams).toEqual({param: 'value'});
        });
    });
})(window);
