LocationMockService = (function (jasmine) {
    var service = function () {
        this.locationMock = jasmine.createSpyObj('location', ['protocol', 'host']);
        this.$host = '## FILL HOSTNAME ##',
        this.$protocol = 'https'
    };

    service.prototype.getLocationMock = function () {
        var $host = this.$host;
        var $protocol = this.$protocol;

        this.locationMock.host.andCallFake(function () {
            return $host;
        });
        this.locationMock.protocol.andCallFake(function () {
            return $protocol;
        });

        return this.locationMock;
    }

    return service;
})(jasmine);