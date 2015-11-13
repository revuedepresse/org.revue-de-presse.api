'use strict';
/*eslint-env jasmine, jquery */
describe('Job', function () {
    var jobsBoard;
    var jobsList;
    var exportPerspectivesButton;
    var body = $('body');
    var createdJobEvent = 'job:created';
    var eventListeners;
    var jobsListItems;
    var jobsListItemsSelector = '[data-listen-event="' + createdJobEvent + '"] li';
    var exportPerspectivesAction = 'export-perspectives';
    var authorizationHeaderValue = 'Bearer tok';

    beforeEach(function () {
        jobsList = $('<ul />', {'data-listen-event': createdJobEvent});
        body.append(jobsList);

        exportPerspectivesButton = $('<button />', {'data-action': exportPerspectivesAction});
        body.append(exportPerspectivesButton);

        eventListeners = [
            {
                name: 'export-perspectives',
                type: 'click',
                listeners: $('[data-action="'+ exportPerspectivesAction + '"]'),
                request: {
                    uri: 'http://localhost/export-perspectives',
                    method: 'post',
                    headers: [
                        {
                            key: 'Authorization',
                            value: authorizationHeaderValue
                        }
                    ],
                    success: {
                        emit: createdJobEvent
                    }
                }
            }, {
                name: 'post-job-creation',
                type: createdJobEvent,
                listeners: $('[data-listen-event="' + createdJobEvent + '"]')
            }
        ];
        jobsBoard = window.getJobsBoard($, eventListeners);
    });

    afterEach(function () {
        exportPerspectivesButton.remove();
        jobsList.remove();
    });

    it('should export perspectives', function (done) {
        var requestMockery = RequestMockery(eventListeners[0].request.uri);
        requestMockery.shouldPost();
        requestMockery.respondWith({
            job: {
                id: 1
            },
            result: 'About to export perspectives',
            type: 'success'
        });
        requestMockery.setRequestHandler(function (settings) {
            expect(settings.headers).not.toBeUndefined();
            expect(settings.headers.Authorization)
                .toEqual(authorizationHeaderValue);
        });
        var mock = requestMockery.mock();

        jobsBoard.mount({'post-job-creation': function () {
            jobsListItems = $(jobsListItemsSelector);
            expect(jobsListItems.length).toEqual(1);
            done();
        }});

        $('[data-action="' + exportPerspectivesAction + '"]').click();
        mock.destroy();
    });
});
