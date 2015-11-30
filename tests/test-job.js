'use strict';

(function (require) {
    var RequestMockery = require.RequestMockery;
    var getJobsBoard = require.getJobsBoard;

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

        var job;
        var pollJobAction = 'poll-job';
        var polledJobEvent = 'job:polled';
        var pollJobSelector = '[data-action="{{ action }}"]'
            .replace('{{ action }}', pollJobAction);
        var polledJobSelector = '[data-listen-event="{{ event_type }}"]'
            .replace('{{ event_type }}', polledJobEvent);
        var getPollJobUrl = function (jobId) {
            return remoteUrl + '/api/job/{{ job_id }}/output'
                .replace('{{ job_id }}', jobId);
        };

        var saveJobArchiveAction = 'save-job-archive';
        var saveJobArchiveButton;

        var headers = [
            {
                key: 'Authorization',
                value: authorizationHeaderValue
            }
        ];

        var jobsBoard;
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
            jasmine.DEFAULT_TIMEOUT_INTERVAL = 15000;

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

            job = $('<div />', {
                'data-action': pollJobAction,
                'data-entity': 'job',
                'data-id': 1
            });
            var statusColumn = $('<span />', {
                'data-column-name': 'Status'
            });
            job.append(statusColumn);
            var outputColumn = $('<span />', {
                'data-column-name': 'Output'
            });
            job.append(outputColumn);

            body.append(job);
        });

        afterEach(function () {
            exportPerspectivesButton.remove();
            container.remove();
            saveJobArchiveButton.remove();
            job.remove();
        });

        it('should list jobs', function (done) {
            eventListeners = getListJobsEventListener();
            var mock = mockListJobsEventListenerRequest(
                eventListeners[0].request.url
            );

            jobsBoard = getJobsBoard($, eventListeners);

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

        it('should refresh a job status via polling', function (done) {
            var output = '/job/1/archive';
            var status = 'The jobs #1 has successfully finished';
            var pollJobListener = 'poll-job';

            jasmine.clock().install();

            eventListeners = [
                {
                    listeners: $(pollJobSelector),
                    name: pollJobListener,
                    request: {
                        url: getPollJobUrl,
                        headers: headers,
                        success: {
                            emit: polledJobEvent
                        }
                    },
                    type: 'polling'
                }, {
                    listeners: $(polledJobSelector),
                    name: 'post-poll-job',
                    type: polledJobEvent
                }
            ];

            logger.enableLogging();
            logger.setLoggingLevel(logger.LOGGING_LEVEL.ERROR);

            requestMockery = RequestMockery(getPollJobUrl(1));
            requestMockery.respondWith({
                entity: 'job',
                id: 1,
                columns: {
                    rlk_Output: output,
                    Status: status
                },
                type: 'success'
            });
            requestMockery.setRequestHandler(function (settings) {
                expect(settings.headers).not.toBeUndefined();
                expect(settings.headers.Authorization)
                    .toEqual(authorizationHeaderValue);
            });
            var mock = requestMockery.mock();

            jobsBoard = getJobsBoard($, eventListeners);
            job.attr('data-listen-event', jobsBoard.events.polledJob);
            jobsBoard.mount({
                'post-poll-job': function () {
                    var attribute = 'data-column-name';
                    var outputJobColumn = job.find('[{{ attribute }}="Output"]'
                        .replace('{{ attribute }}', attribute));
                    expect(outputJobColumn.length)
                        .not.toEqual(0);
                    var downloadJobArchiveLink = outputJobColumn.find('a');
                    expect(downloadJobArchiveLink.length).toEqual(1);
                    expect(downloadJobArchiveLink.attr('data-url')).toEqual(
                        jobsBoard.remote + output
                    );

                    var statusJobColumn = job.find('[{{ attribute }}="Status"]'
                        .replace('{{ attribute }}', attribute));
                    expect(statusJobColumn.length)
                        .not.toEqual(0);
                    expect(statusJobColumn.text()).toEqual(status);

                    // (2) The repeated action should have been cancelled
                    // once the targetted changes have been applied
                    expect(jobsBoard.polling[pollJobListener]).toBeUndefined();

                    done();
                }
            });

            // (1) Trigger interval callback
            jasmine.clock().tick(jobsBoard.DEFAULT_POLLING_INTERVAL_DELAY + 1);
            expect(jobsBoard.polling[pollJobListener]).toEqual(1);

            jasmine.clock().uninstall();

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

            jobsBoard = getJobsBoard($, eventListeners);

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

            jobsBoard = getJobsBoard($, eventListeners);

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
