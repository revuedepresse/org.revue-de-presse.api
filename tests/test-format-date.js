'use strict';

(function (require) {
    var getStringFormatter = require.getStringFormatter;

    describe('Date formatting', function () {
        var stringFormatter;

        beforeEach(function () {
            stringFormatter = getStringFormatter();
        });

        it('should convert a date to the local timezone', function () {
            var preventSlicing = true;
            var universalDate = '2015-10-31T00:50:11.000Z';
            var formattedDate = stringFormatter.formatDate(universalDate, preventSlicing);
            expect(formattedDate).toEqual('2015-10-31T01:50:11.000Z');

            formattedDate = stringFormatter.formatDate(universalDate);
            expect(formattedDate).toEqual('2015-10-31');
        });
    });
})(this);
