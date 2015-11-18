'use strict';

(function (exports) {
    exports.getNotificationCenter = function (notificationId, $) {
        var NotificationCenter = function (notificationId) {
            this.notification = $('#' + notificationId);
        };

        NotificationCenter.prototype.showNotification = function (data) {
            if (data.type !== 'danger') {
                this.notification.text(data.result);
            } else {
                this.notification.html(data.result);
            }

            this.notification.parent().removeClass('alert-error');
            this.notification.parent().removeClass('alert-danger');
            this.notification.parent().removeClass('alert-block');
            this.notification.parent().removeClass('alert-success');
            this.notification.parent().addClass('alert alert-' + data.type);
        };

        return new NotificationCenter(notificationId);
    };
})(this);

if (this.jQuery) {
    this.notificationCenter = this.getNotificationCenter(
        'notification',
        this.jQuery
    );
}
