'use strict';

var require = this;
var mountDashboard = require.mountDashboard;
var getNotificationCenter = require.getNotificationCenter;
var RequestMockery = require.RequestMockery;

describe('Dashboard', function () {
    var dashboard;

    var elementReferences = {};

    // Queries
    var formElement;
    var queryContainerElement;
    var queryTextAreaElement;
    var saveQueryButtonElement;

    var queryClass = 'query';
    var sqlClass = 'sql';

    var editQueryId = 'edit-query';
    var queryContainerId = 'query';
    var queryTextAreaId = 'sql';
    var saveQueryButtonId = 'action-save-query';

    // Perspectives
    var exportPerspectiveContainerElement;
    var hashClass = 'hash';
    var perspectiveClass = 'perspective';

    // JSON perspectives
    var jsonPerspectiveContainerElement;
    var jsonPerspectiveClass = 'perspective-json';
    var perspectiveHash = 'my-perspective-hash';

    var routes = {
        saveQuery: '/save-query',
        exportPerspective: function (hash) {
            return '/export-perspective/' + hash;
        },
        showPerspective: function (hash) {
            return '/show-perspective/' + hash;
        }
    };

    var saveQueryResult = 'Execution effectuée avec succès';
    var exportQueryExecutionResult = 'Requête effectuée';

    var notificationCenterMock = require.mockNotificationCenter($);

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
            }, elementReferences
        );
    }

    /**
     * Create an HTML element containing a perspective hash and a button to export it
     */
    function createPerspectiveContainer() {
        return createContainerElement(
            {
                'class': perspectiveClass
            }, {
                'class': hashClass,
                text:  'hash'
            }, elementReferences
        );
    }

    /**
     * Create an HTML element containing a perspective hash and a button to navigate to it
     */
    function createJsonPerspectiveContainer() {
        return createContainerElement(
            {
                'class': jsonPerspectiveClass
            }, {
                'class': hashClass,
                text: 'my-perspective-hash'

            }, elementReferences
        );
    }

    beforeEach(function () {
        queryTextAreaElement = $('<textarea />', {id: queryTextAreaId});
        formElement = $('<form />', {id: editQueryId});
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

        jsonPerspectiveContainerElement = createJsonPerspectiveContainer();
        bodyElement.append(jsonPerspectiveContainerElement);

        // Ensure "before each" operations went smoothly
        expect(saveQueryButtonElement[0]).not.toBeUndefined();
        dashboard = mountDashboard({
            $: $,
            routes: routes,
            notificationCenter: getNotificationCenter(
                notificationCenterMock.getNotificationElementId(),
                $
            )
        });
    });

    afterEach(function () {
        queryContainerElement.remove();
        saveQueryButtonElement.remove();
        exportPerspectiveContainerElement.remove();
        jsonPerspectiveContainerElement.remove();
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
            var query = '# show ' + '\n' +
                'SELECT @id;';
            assertQuerySubmittedForExecution(query, done);
        });

        it('should submit a query starting with a compliant comment', function (done) {
            var query = '# count' + '\n' +
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
        });
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
        );
    });

    describe('Go to JSON perspective', function () {
        it('should navigate to a JSON perspective when clicking on a button',
            function (done) {
                var navigationFormElement = dashboard.getNavigationForm();
                var perspectiveUrl = routes.showPerspective(perspectiveHash);
                navigationFormElement.submit(function (event) {
                    event.preventDefault();

                    expect($(event.target).attr('action')).toEqual(perspectiveUrl);

                    done();

                    return false;
                });

                var jsonPerspectiveButton = elementReferences[jsonPerspectiveClass].button;
                jsonPerspectiveButton.click();
            }
        );
    });
});
