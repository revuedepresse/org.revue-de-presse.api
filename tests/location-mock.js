
(function (exports, require) {
    var jasmine = require.jasmine;

    exports.getLocationMockService = function (jasmine) {
        var service = function () {
            this.locationMock = jasmine.createSpyObj(
                'location',
                ['host', 'protocol']
            );
            this.$host = '## FILL HOSTNAME ##';
            this.$protocol = 'https';
        };

        service.prototype.getLocationMock = function () {
            var $host = this.$host;
            var $protocol = this.$protocol;

            this.locationMock.host = jasmine.createSpy().and.returnValue($host);
            this.locationMock.protocol = jasmine.createSpy().and.returnValue($protocol);

            return this.locationMock;
        };

        return service;
    };

    exports.LocationMockService = require.getLocationMockService(jasmine);
})(this, this);
