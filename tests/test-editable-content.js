'use strict';

describe('Editable Content', function () {
    var editableContentElement;

    var routes = {
        saveContent: function (tab, key, col) {
            return '/save-content/' + tab + '/' + key + '/' + col;
        }
    };

    var notificationCenterMock = mockNotificationCenter($);

    var failureWhenContentSaving = 'Sorry, this content could not be saved';

    var editableContentsSelector = '.editable';

    beforeEach(function () {
        var bodyElement = $('body');
        notificationCenterMock.beforeEach();
        bodyElement.append(notificationCenterMock.getNotificationCenterElement());

        editableContentElement = $('<pre />', {
            'class': editableContentsSelector.substr(1),
            'contenteditable': true,
            'data-col': 'column',
            'data-key': 'key',
            'data-tab': 'table'
        });
        bodyElement.append(editableContentElement);

        mountEditableContents({
            selector: editableContentsSelector,
            routes: routes,
            notificationCenter: getNotificationCenter(notificationCenterMock.getNotificationElementId(), $),
            $: $
        });
    });

    afterEach(function () {
        editableContentElement.remove();
        notificationCenterMock.afterEach();
    });

    var getSaveContentRequestMockery = function (done, content) {
        var mockery = RequestMockery(routes.saveContent('table', 'key', 'column'));
        mockery.onAfterComplete(notificationCenterMock.assertNotifyError(done, 'danger'));
        mockery.shouldPost();
        mockery.sendData({content: content});
        mockery.setStatusCode(400);
        mockery.respondWith({
            result: failureWhenContentSaving,
            type: 'error'
        });

        return mockery;
    };

    it('should post editable content to save it', function (done) {
        var content = 'content';
        var mockery = getSaveContentRequestMockery(done, content);
        var mock = mockery.mock();
        editableContentElement.text(content);

        // click on the body to lose focus on the editable content
        $(editableContentElement).focusout();

        mock.destroy();
    });
});
