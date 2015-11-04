
function mockNotificationCenter($) {
    var NotificationCenterMock = function () {
        this.notificationCenterId = 'notification-center';
        this.notificationCenterElement = {};

        this.notificationElementId = 'notification';
        this.notificationElement = {};
    };

    NotificationCenterMock.prototype.beforeEach = function () {
        this.notificationCenterElement = $('<div />', {id: this.notificationCenterId});
        this.notificationElement = $('<div />', {id: this.notificationElementId});
        this.notificationCenterElement.append(this.notificationElement);
    };

    NotificationCenterMock.prototype.afterEach = function () {
        this.notificationCenterElement.remove();
    };

    NotificationCenterMock.prototype.getNotificationCenterElement = function () {
        return this.notificationCenterElement;
    };

    NotificationCenterMock.prototype.getNotificationElementId = function () {
        return this.notificationElementId;
    };

    NotificationCenterMock.prototype.assertNotifyError = function(done, alertType) {
        var self  = this;
        if (alertType === undefined) {
            alertType = 'error';
        }

        return function () {
            expect(self.notificationCenterElement.hasClass('alert-' + alertType)).toBeTruthy();
            expect(self.notificationCenterElement.hasClass('alert-success')).toBeFalsy();

            if (typeof done === 'function') {
                done();
            }
        };
    };

    NotificationCenterMock.prototype.assertNotifySuccess = function (done) {
        var self = this;

        return function () {
            expect(self.notificationCenterElement.hasClass('alert-success')).toBeTruthy();
            expect(self.notificationCenterElement.hasClass('alert-error')).toBeFalsy();

            if (typeof done === 'function') {
                done();
            }
        }
    };

    NotificationCenterMock.prototype.assertNotifyCustomMessage = function (message, done) {
        var self = this;
        return function () {
            expect(self.notificationElement[0]).not.toBeUndefined();
            expect(self.notificationElement.text()).toEqual(message);
            done();
        }
    };

    return new NotificationCenterMock();
}
