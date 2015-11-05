'use strict';

describe('Dashboard', function () {
    var dashboard;

    var queryContainerId = 'query';
    var queryContainerElement;

    var elementReferences = {};

    var hashClass = 'hash';
    var perspectiveClass = 'perspective';
    var queryClass = 'query';
    var sqlClass = 'sql';

    var queryTextAreaId = 'sql';
    var queryTextAreaElement;

    var formElement;

    var saveQueryButtonId = 'action-save-query';
    var saveQueryButtonElement;

    var exportPerspectiveContainerElement;

    var routes = {
        saveQuery: '/save-query',
        exportPerspective: function (hash) {
            return '/export-perspective/' + hash;
        }
    };

    var saveQueryResult = 'Execution effectuée avec succès';
    var exportQueryExecutionResult = 'Requête effectuée';

    var notificationCenterMock = mockNotificationCenter($);

    var createContainerElement = function (containerProperties, childProperties, elementReferences) {
        if (!containerProperties.hasOwnProperty('class')) {
            throw Error('The container element to be created should have a "class" property.');
        }

        elementReferences[containerProperties['class']] = {};

        var containerElement = $('<div />', containerProperties);
        var references = elementReferences[containerProperties['class']];

        references.button = $('<button />');
        containerElement.append(references.button);

        references.contentPlaceholder = $('<span />', childProperties);
        containerElement.append(references.contentPlaceholder);

        return containerElement;
    };

    /**
     * Create an HTML element containing an SQL query and a button to execute it
     */
    function createQueryContainer() {
        return createContainerElement(
            {
                id: queryContainerId,
                'class': queryClass
            }, {
                'class': sqlClass
            },
            elementReferences
        );
    }

    /**
     * Create an HTML element containing a perspective and a button to export it
     */
    function createPerspectiveContainer() {
        return createContainerElement(
            {
                id: queryContainerId,
                'class': perspectiveClass
            }, {
                'class': hashClass,
                text:  'hash'
            },
            elementReferences
        );
    }

    beforeEach(function () {
        queryTextAreaElement = $('<textarea />', {id: queryTextAreaId});
        formElement = $('<form />', {id: 'edit-query'});
        formElement.append(queryTextAreaElement);

        var bodyElement = $('body');

        bodyElement.addClass('container');
        bodyElement.append(formElement);

        notificationCenterMock.beforeEach();
        bodyElement.append(notificationCenterMock.getNotificationCenterElement());

        saveQueryButtonElement = $('<button/>', {id: saveQueryButtonId});
        bodyElement.append(saveQueryButtonElement);

        exportPerspectiveContainerElement = createPerspectiveContainer();
        bodyElement.append(exportPerspectiveContainerElement);

        queryContainerElement = createQueryContainer();
        bodyElement.append(queryContainerElement);

        navigationFormElement = $('<form />', {id: 'navigator', 'method': 'GET'});
        bodyElement.append(navigationFormElement);

        // Ensure "before each" operations went smoothly
        expect(saveQueryButtonElement[0]).not.toBeUndefined();
        dashboard = mountDashboard({
            $: $,
            routes: routes,
            notificationCenter: getNotificationCenter(notificationCenterMock.getNotificationElementId(), $)
        });
    });

    afterEach(function () {
        queryContainerElement.remove();
        saveQueryButtonElement.remove();
        exportPerspectiveContainerElement.remove();
        formElement.remove();

        notificationCenterMock.afterEach();
    });

    function getSaveSqlRequestMockery(done) {
        var requestMockery = RequestMockery(routes.saveQuery);
        requestMockery.onAfterSuccess(notificationCenterMock.assertNotifyError(done));
        requestMockery.shouldPost();
        requestMockery.respondWith({
            result: saveQueryResult,
            type: 'error'
        });

        return requestMockery;
    }

    function getExportPerspectiveRequestMockery(done, hash) {
        var requestMockery = RequestMockery(routes.exportPerspective(hash));
        requestMockery.onAfterSuccess(notificationCenterMock.assertNotifyError(done, 'danger'));
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
        mockery.onAfterSuccess(notificationCenterMock.assertNotifySuccess(done));
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
                mockery.onAfterSuccess(notificationCenterMock.assertNotifyCustomMessage(saveQueryResult, done));
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
        var references = elementReferences[queryClass];
        references.contentPlaceholder.text(query);

        formElement.submit(function (event) {
            event.stopPropagation();
            event.preventDefault();

            if (typeof done === 'function') {
                // Query execution requires submitting a form
                done();
            }

            return false;
        });
        references.button.click();
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
                var button = elementReferences[perspectiveClass].button;
                var event = button.click
                    .bind(button);
                var hash = button.parent().find('.hash').text();
                var mockery = getExportPerspectiveRequestMockery(done, hash);

                assertErrorNotificationExistsOnRequest(event, mockery);
            }
        )
    });
});
