'use strict';
/*eslint-env jasmine, jquery */
describe('Job', function () {
    var authorizationHeaderValue = 'Bearer tok';
    var body = $('body');
    var container;
    var containerName = 'jobs-board';
    var containerSelector = '[data-container="' + containerName + '"]';
    var exportPerspectivesAction = 'export-perspectives';
    var exportPerspectivesButton;
    var exportPerspectivesSelector;
    var createdJobEvent = 'job:created';
    var eventListeners;
    var jobsBoard;
    var jobsListItems;
    var jobsListItemsSelectorTemplate = '[data-listen-event="{{ event_type }}"] li';
    var jobsListItemsSelector = jobsListItemsSelectorTemplate
        .replace('{{ event_type }}', createdJobEvent);
    var headers = [
        {
            key: 'Authorization',
            value: authorizationHeaderValue
        }
    ];
    var listedJobEvent = 'job:listed';
    var requestMockery;

    beforeEach(function () {
        container = $('<div />', {'data-container': containerName});
        body.append(container);

        exportPerspectivesButton = $('<button />', {
            'data-action': exportPerspectivesAction
        });
        body.append(exportPerspectivesButton);

        exportPerspectivesSelector = '[data-action="{{ action }}"]'
            .replace('{{ action }}', exportPerspectivesAction);
    });

    afterEach(function () {
        exportPerspectivesButton.remove();
    });

    it('should list jobs', function (done) {
        eventListeners = [
            {
                name: 'list-jobs',
                type: 'load',
                listeners: $('body'),
                request: {
                    uri: 'http://localhost/jobs',
                    headers: headers,
                    success: {
                        emit: listedJobEvent
                    }
                }
            }, {
                container: $(containerSelector),
                name: 'post-jobs-listing',
                type: listedJobEvent
            }
        ];
        requestMockery = RequestMockery(eventListeners[0].request.uri);
        requestMockery.respondWith({
            collection: [{
                id: 1,
                status: 1
            }],
            type: 'success'
        }).setRequestHandler(function (settings) {
            expect(settings.headers).not.toBeUndefined();
            expect(settings.headers.Authorization)
                .toEqual(authorizationHeaderValue);
        });
        var mock = requestMockery.mock();

        jobsBoard = window.getJobsBoard($, eventListeners);
        jobsBoard.mount({'post-jobs-listing': function () {
            var jobsListItemsSelector = jobsListItemsSelectorTemplate
                .replace('{{ event_type }}', listedJobEvent);
            jobsListItems = $(jobsListItemsSelector);
            expect(jobsListItems.length).toEqual(1);
            done();
        }});

        $('body').load();  

        mock.destroy();
    });

    it('should export perspectives', function (done) {
        eventListeners = [
            {
                listeners: $(exportPerspectivesSelector),
                name: 'export-perspectives',
                request: {
                    uri: 'http://localhost/perspective/export',
                    method: 'post',
                    headers: headers,
                    success: {
                        emit: createdJobEvent
                    }
                },
                type: 'click'
            }, {
                container: $(containerSelector),
                name: 'post-job-creation',
                type: createdJobEvent
            }
        ];
        requestMockery = RequestMockery(eventListeners[0].request.uri);
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

        jobsBoard = window.getJobsBoard($, eventListeners);
        jobsBoard.mount({'post-job-creation': function () {
            var jobsListItems = $(jobsListItemsSelector);
            expect(jobsListItems.length).toEqual(1);
            done();
        }});

        $(exportPerspectivesSelector).click();
        mock.destroy();
    });
});
