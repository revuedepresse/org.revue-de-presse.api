'use strict';

describe('Dashboard', function () {
    var dashboard;

    var queryId = 'query';
    var queryElement;

    var executeQueryButtonElement;

    var sqlClass = 'sql';
    var sqlElement;

    var queryTextAreaId = 'sql';
    var queryTextAreaElement;

    var formElement;

    var saveQueryButtonId = 'action-save-query';
    var saveQueryButtonElement;

    var notificationCenterId = 'notification-center';
    var notificationCenterElement;

    var notificationId = 'notification';
    var notificationElement;

    var routes = {
        saveSql: '/save-sql'
    };

    var saveQueryResult = 'Execution effectuée avec succès';
    var saveRequestMockId;

    beforeEach(function () {
        queryElement = $('<div />', {
            id: queryId,
            class: "query"
        });
        executeQueryButtonElement = $('<button />');
        queryElement.append(executeQueryButtonElement);

        sqlElement= $('<span />', {'class': sqlClass});
        queryElement.append(sqlElement);

        saveQueryButtonElement = $('<button >', {
            id: saveQueryButtonId
        });

        notificationCenterElement = $('<div />', {id: notificationCenterId});
        notificationElement = $('<div />', {id: notificationId});
        notificationCenterElement.append(notificationElement);

        queryTextAreaElement = $('<textarea />', {id: queryTextAreaId});
        formElement = $('<form />');
        formElement.append(queryTextAreaElement);

        var bodyElement = $('body');

        bodyElement.append(queryElement);
        bodyElement.append(saveQueryButtonElement);
        bodyElement.append(notificationCenterElement);

        bodyElement.addClass('container');
        bodyElement.append(formElement);

        $.mockjaxSettings.throwUnmocked = true;
        $.mockjaxSettings.logging = false;
        $.mockjaxSettings.responseTime = 0;

        // Ensure "before each" operations went smoothly
        expect(saveQueryButtonElement[0]).not.toBeUndefined();
        dashboard = mountDashboard($, routes);
    });

    afterEach(function () {
        queryElement.remove();
        saveQueryButtonElement.remove();
        notificationCenterElement.remove();
        formElement.remove();
    });

    function mockRequest(type, assert) {
        return $.mockjax({
            url: routes.saveSql,
            type: 'POST',
            responseText: {
                result: saveQueryResult,
                type: type
            },
            onAfterSuccess: assert,
            onAfterError: function (error) {
                expect(error).toBeNull();
            }
        });
    }

    function assertErrorNotificationExists(done) {
        saveRequestMockId = mockRequest('error', function () {
            expect(notificationCenterElement.hasClass('alert-error')).toBeTruthy();
            expect(notificationCenterElement.hasClass('alert-success')).toBeFalsy();
            done();
        });
        saveQueryButtonElement.click();
        $.mockjax.clear(saveRequestMockId);
    }

    function assertSuccessNotificationExists(done) {
        saveRequestMockId = mockRequest('success', function () {
            expect(notificationCenterElement.hasClass('alert-success')).toBeTruthy();
            expect(notificationCenterElement.hasClass('alert-error')).toBeFalsy();
            if (typeof done === 'function') {
                done();
            }
        });
        saveQueryButtonElement.click();
        $.mockjax.clear(saveRequestMockId);
    }

    describe('Save query', function () {
        it('should show a recorded query when clicking on a button, which parent element has a "query" css class',
            function (done) {
                saveRequestMockId = mockRequest('success', function () {
                    expect(notificationElement[0]).not.toBeUndefined();
                    expect(notificationElement.text()).toEqual(saveQueryResult);
                    done();
                });
                saveQueryButtonElement.click();
                $.mockjax.clear(saveRequestMockId);
            }
        );

        it('should show a success notification when a query has been saved successfully.', function (done) {
            assertSuccessNotificationExists();
            assertErrorNotificationExists(done);
        });

        it('should show an error notification when a query has not been saved successfully.', function (done) {
            assertErrorNotificationExists(done);
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
        submitQuery(query, done);
        expect(queryTextAreaElement.value).toEqual(query);
    }

    describe('Execute query', function () {
        it('should fill out a form to submit a query for execution.', function (done) {
            var query = '# show ' + "\n" +
                'SELECT @id;';
            assertQuerySubmittedForExecution(query, done);
        });

        it('should submit a query containing a compliant comment', function (done) {
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
        })
    });
});