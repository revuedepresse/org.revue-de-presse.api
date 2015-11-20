'use strict';

(function (require) {
    var RequestMockery = require.RequestMockery;

    describe('Job', function () {
        var authorizationHeaderValue = 'Bearer tok';
        var body = $('body');

        var container;
        var containerName = 'jobs-board';
        var containerSelector = '[data-container="' + containerName + '"]';

        var downloadJobArchiveAction = 'download-job-archive';
        var downloadedJobArchiveEvent = 'job:archive:downloaded';
        var downloadJobArchiveUrl = '/archive';
        var downloadJobArchiveSelector = '[data-action="' +
            downloadJobArchiveAction + '"]';

        var exportPerspectivesAction = 'export-perspectives';
        var exportPerspectivesButton;
        var exportPerspectivesSelector;

        var createdJobEvent = 'jobs:created';

        var eventListeners;

        var jobsListItems;
        var jobsListItemsSelectorTemplate = '[data-listen-event="{{ event_type }}"] tr';
        var jobsListItemsSelector = jobsListItemsSelectorTemplate
            .replace('{{ event_type }}', createdJobEvent);
        var listedJobEvent = 'jobs:listed';

        var logger = require.logger;

        var remoteUrl = 'http://localhost';

        var saveJobArchiveAction = 'save-job-archive';
        var saveJobArchiveButton;

        var headers = [
            {
                key: 'Authorization',
                value: authorizationHeaderValue
            }
        ];

        var requestMockery;

        function getListJobsEventListener() {
            return [
                {
                    name: 'list-jobs',
                    type: 'load',
                    listeners: $('body'),
                    request: {
                        url: remoteUrl + '/jobs',
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
        }

        function mockListJobsEventListenerRequest(url) {
            requestMockery = RequestMockery(url);
            requestMockery.respondWith({
                collection: [{
                    Id: 1,
                    Status: 1,
                    rlk_Output: downloadJobArchiveUrl,
                    id: 1,
                    entity: 'job'
                }],
                type: 'success'
            }).setRequestHandler(function (settings) {
                expect(settings.headers).not.toBeUndefined();
                expect(settings.headers.Authorization)
                    .toEqual(authorizationHeaderValue);
            });

            return requestMockery.mock();
        }

        beforeEach(function () {
            container = $('<div />', {'data-container': containerName});
            body.append(container);

            exportPerspectivesButton = $('<button />', {
                'data-action': exportPerspectivesAction
            });
            body.append(exportPerspectivesButton);

            exportPerspectivesSelector = '[data-action="{{ action }}"]'
                .replace('{{ action }}', exportPerspectivesAction);

            saveJobArchiveButton = $('<button />', {
                'data-action': saveJobArchiveAction,
                'data-listen-event': downloadedJobArchiveEvent,
                text: 'Save job archive'
            });
            body.append(saveJobArchiveButton);
        });

        afterEach(function () {
            exportPerspectivesButton.remove();
            container.remove();
            saveJobArchiveButton.remove();
        });

        it('should list jobs', function (done) {
            eventListeners = getListJobsEventListener();
            var mock = mockListJobsEventListenerRequest(
                eventListeners[0].request.url
            );

            var jobsBoard = window.getJobsBoard($, eventListeners);

            logger.enableLogging();
            logger.setLoggingLevel(logger.LOGGING_LEVEL.WARN);

            jobsBoard.setRemote(remoteUrl);
            jobsBoard.mount({
                'post-jobs-listing': function () {
                    var jobsListItemsSelector = jobsListItemsSelectorTemplate
                        .replace('{{ event_type }}', listedJobEvent);
                    jobsListItems = $(jobsListItemsSelector);

                    // It should create a table head containing a single row
                    // It should create a table body containing a single row
                    expect(jobsListItems.length).toEqual(2);

                    // It should form columns prefixed with "rlk_"
                    expect($(jobsListItems[1]).find('a')).toBeTruthy();
                    done();
                }
            });

            $('body').load();

            mock.destroy();
        });

        it('should export perspectives', function (done) {
            eventListeners = [
                {
                    listeners: $(exportPerspectivesSelector),
                    name: 'export-perspectives',
                    request: {
                        url: remoteUrl + '/perspective/export',
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
            requestMockery = RequestMockery(eventListeners[0].request.url);
            requestMockery.shouldPost();
            requestMockery.respondWith({
                job: {
                    Id: 1,
                    Status: 'A new idle job has been created.',
                    entity: 'job',
                    rlk_Output: null
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

            var jobsBoard = window.getJobsBoard($, eventListeners);

            logger.enableLogging();
            logger.setLoggingLevel(logger.LOGGING_LEVEL.WARN);

            jobsBoard.mount({
                'post-job-creation': function () {
                    var jobsListItems = $(jobsListItemsSelector);
                    // It should create the table, its header and its body.
                    // It should receive data asynchronously.
                    // It should append a row containing columns names to the header.
                    // It should append a row containing data to the bodies.
                    expect(jobsListItems.length).toEqual(2);

                    // It should contain a row with a formatted Output column

                    var outputColumnSelector = jobsListItemsSelector +
                        ' [data-column-name="Output"]';
                    expect($(outputColumnSelector).length).toEqual(1);

                    done();
                }
            });

            $(exportPerspectivesSelector).click();
            mock.destroy();
        });

        it('should download an archive', function (done) {
            eventListeners = getListJobsEventListener();
            var listJobsMock = mockListJobsEventListenerRequest(
                eventListeners[0].request.url
            );
            var downloadJobArchiveMock;

            eventListeners.push({
                after: 'post-jobs-listing',
                listeners: $(downloadJobArchiveSelector),
                name: 'download-job-archive',
                request: {
                    //// This request should have the "contentType" property
                    //// but the "native" data type is not taken care of
                    //// by $.mockjax as we rely on jqueryNativeAjax plugin
                    //// to download zip archives as a native Blob
                    //// See PR https://github.com/jquery/jquery/pull/1525
                    //// Whenever mockjax would be adapted to support "native"
                    //// date type or anything alike, the data type expectation
                    //// below would need to be updated accordingly
                    // contentType: 'application/zip',
                    headers: headers,
                    success: {
                        emit: downloadedJobArchiveEvent
                    }
                },
                type: 'click'
            });
            eventListeners.push({
                after: 'download-job-archive',
                listeners: $(saveJobArchiveButton),
                name: 'post-job-archive-download',
                type: downloadedJobArchiveEvent
            });

            requestMockery = RequestMockery(remoteUrl + downloadJobArchiveUrl);
            requestMockery.respondWith('binarycontent')
                .setRequestHandler(function (settings) {
                    expect(settings.headers).not.toBeUndefined();
                    expect(settings.headers.Authorization)
                        .toEqual(authorizationHeaderValue);
                    expect(settings.dataType).not.toBeUndefined();
                    expect(settings.dataType).toEqual('*');
                });

            var jobsBoard = window.getJobsBoard($, eventListeners);

            logger.enableLogging();
            logger.setLoggingLevel(logger.LOGGING_LEVEL.DEBUG);
            logger.filterLogByLevel(logger.LOGGING_LEVEL.ERROR);

            try {
                var fileSaverMock = jasmine.createSpy('saveAs');
                jobsBoard.setFileSaver(fileSaverMock);

                jobsBoard.setRemote(remoteUrl);

                jobsBoard.mount({
                    'post-jobs-listing': function () {
                        expect($(downloadJobArchiveSelector).length).toEqual(1);
                    },
                    'download-job-archive': function () {
                        downloadJobArchiveMock = requestMockery.mock();

                        // Ensures onload body event handler
                        // previously attached on "jobs:listed" event
                        // has been detached
                        expect($(downloadJobArchiveSelector).length).toEqual(1);

                        // (2) Click on download archive button
                        $(downloadJobArchiveSelector).click();
                        downloadJobArchiveMock.destroy();
                    },
                    'post-job-archive-download': function () {
                        expect(fileSaverMock).toHaveBeenCalled();

                        done();
                    }
                });
            } catch (error) {
                jobsBoard.logger.error(error.stack);
            }

            // (1) Get jobs list
            $('body').load();
            listJobsMock.destroy();
        });
    });
})(window);
