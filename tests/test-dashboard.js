'use strict';

describe('Dashboard', function () {
    var dashboard;

    var queryContainerId = 'query';
    var queryContainerElement;

    var executeQueryButtonElement;

    var sqlClass = 'sql';
    var sqlElement;

    var queryTextAreaId = 'sql';
    var queryTextAreaElement;

    var formElement;

    var saveQueryButtonId = 'action-save-query';
    var saveQueryButtonElement;

    var exportQueryExecutionResultsButtonId = 'action-export-query-execution-results';
    var exportQueryExecutionResultsButtonElement;

    var notificationCenterId = 'notification-center';
    var notificationCenterElement;

    var notificationId = 'notification';
    var notificationElement;

    var routes = {
        saveQuery: '/save-query',
        exportQueryExecutionResults: '/export-query-execution-results'
    };

    var saveQueryResult = 'Execution effectuée avec succès';
    var exportQueryExecutionResult = 'Requête effectuée';

    /**
     * Create a HTML element containing a SQL query and a button to execute it
     */
    function createQueryContainer() {
        var containerElement = $('<div />', {
            id: queryContainerId,
            class: "query"
        });
        executeQueryButtonElement = $('<button />');
        containerElement.append(executeQueryButtonElement);

        sqlElement = $('<span />', {
            'class': sqlClass
        });
        containerElement.append(sqlElement);

        return containerElement;
    }

    beforeEach(function () {
        queryContainerElement = createQueryContainer();

        saveQueryButtonElement = $('<button/>', {
            id: saveQueryButtonId
        });

        exportQueryExecutionResultsButtonElement = $('<button/>', {
            id: exportQueryExecutionResultsButtonId
        });

        notificationCenterElement = $('<div />', {id: notificationCenterId});
        notificationElement = $('<div />', {id: notificationId});
        notificationCenterElement.append(notificationElement);

        queryTextAreaElement = $('<textarea />', {id: queryTextAreaId});
        formElement = $('<form />');
        formElement.append(queryTextAreaElement);

        var bodyElement = $('body');

        bodyElement.addClass('container');
        bodyElement.append(formElement);

        bodyElement.append(notificationCenterElement);
        bodyElement.append(saveQueryButtonElement);
        bodyElement.append(exportQueryExecutionResultsButtonElement);

        bodyElement.append(queryContainerElement);

        // Ensure "before each" operations went smoothly
        expect(saveQueryButtonElement[0]).not.toBeUndefined();
        dashboard = mountDashboard($, routes);
    });

    afterEach(function () {
        queryContainerElement.remove();
        saveQueryButtonElement.remove();
        exportQueryExecutionResultsButtonElement.remove();
        notificationCenterElement.remove();
        formElement.remove();
    });

    function notifyError(done) {
        return function () {
            expect(notificationCenterElement.hasClass('alert-error')).toBeTruthy();
            expect(notificationCenterElement.hasClass('alert-success')).toBeFalsy();

            if (typeof done === 'function') {
                done();
            }
        };
    }

    function getSaveSqlRequestMockery(done) {
        var requestMockery = RequestMockery(routes.saveQuery);
        requestMockery.onAfterSuccess(notifyError(done));
        requestMockery.shouldPost();
        requestMockery.respondWith({
            result: saveQueryResult,
            type: 'error'
        });

        return requestMockery;
    }

    function getExportQueryExecutionResultsRequestMockery(done) {
        var requestMockery = RequestMockery(routes.exportQueryExecutionResults);
        requestMockery.onAfterSuccess(notifyError(done));
        requestMockery.respondWith({
            result: exportQueryExecutionResult,
            type: 'error'
        });

        return requestMockery;
    }

    function assertErrorNotificationExistsOnRequest(event, mockery) {
        var mock = mockery.mock();
        event();
        mock.destroy();
    }

    function assertErrorNotificationExistsOn(event, done) {
        var mockery = getSaveSqlRequestMockery(done);
        var mock = mockery.mock();
        event();
        mock.destroy();
    }

    function assertSuccessNotificationExistsOn(event, done) {
        var mockery = getSaveSqlRequestMockery(done);
        mockery.onAfterSuccess(function () {
            expect(notificationCenterElement.hasClass('alert-success')).toBeTruthy();
            expect(notificationCenterElement.hasClass('alert-error')).toBeFalsy();
            if (typeof done === 'function') {
                done();
            }
        });
        mockery.respondWith({
            result: saveQueryResult,
            type: 'success'
        });
        var mock = mockery.mock();
        event();
        mock.destroy();
    }

    describe('Save query', function () {
        it('should show a recorded query when clicking on a button, which parent element has a "query" css class',
            function (done) {
                var mockery = getSaveSqlRequestMockery(done);
                mockery.onAfterSuccess(function () {
                    expect(notificationElement[0]).not.toBeUndefined();
                    expect(notificationElement.text()).toEqual(saveQueryResult);
                    done();
                });
                var mock = mockery.mock();
                saveQueryButtonElement.click();
                mock.destroy();
            }
        );

        it('should show a success notification when a query has been saved successfully.', function (done) {
            var event = saveQueryButtonElement.click.bind(saveQueryButtonElement);
            assertSuccessNotificationExistsOn(event);
            assertErrorNotificationExistsOn(event, done);
        });

        it('should show an error notification when a query has not been saved successfully.', function (done) {
            var event = saveQueryButtonElement.click.bind(saveQueryButtonElement);
            assertErrorNotificationExistsOn(event, done);
        });
    });

    function submitQuery(query, done) {
        sqlElement.text(query);

        formElement.submit(function (event) {
            event.stopPropagation();
            event.preventDefault();

            if (typeof done === 'function') {
                // Query execution requires submitting a form
                done();
            }

            return false;
        });
        executeQueryButtonElement.click();
    }

    function assertQuerySubmittedForExecution(query, done) {
        submitQuery(query, function () {
            expect(queryTextAreaElement.val()).toEqual(query);
            done();
        });
    }

    describe('Execute query', function () {
        it('should fill out a form to submit a query for execution.', function (done) {
            var query = '# show ' + "\n" +
                'SELECT @id;';
            assertQuerySubmittedForExecution(query, done);
        });

        it('should submit a query starting with a compliant comment', function (done) {
            var query = '# count' + "\n" +
                'SELECT @id;';
            assertQuerySubmittedForExecution(query, done);
        });

        it('should submit a "select" query', function (done) {
            var query = 'SELECT @id;';
            assertQuerySubmittedForExecution(query, done);
        });

        it('should submit a "create table" query.', function (done) {
            var query = 'CREATE TABLE;';
            assertQuerySubmittedForExecution(query, done);
        });

        it('should submit a "drop table" query.', function (done) {
            var query = 'DROP TABLE;';
            assertQuerySubmittedForExecution(query, done);
        });

        it('should not submit an illegal query', function () {
            var query = 'DELETE';
            submitQuery(query);
            expect(queryTextAreaElement.value).not.toEqual(query);
        });

        it('should not submit a query starting with an illegal comment.', function () {
            var query = '# this is not compliant';
            submitQuery(query);
            expect(queryTextAreaElement.value).not.toEqual(query);
        })
    });

    describe('Export query execution results', function () {
        it('should show an error notification when the results of a query execution could not be exported',
            function (done) {
                var event = exportQueryExecutionResultsButtonElement.click
                    .bind(exportQueryExecutionResultsButtonElement);
                var mockery = getExportQueryExecutionResultsRequestMockery(done);

                assertErrorNotificationExistsOnRequest(event, mockery);
            }
        )
    });
});