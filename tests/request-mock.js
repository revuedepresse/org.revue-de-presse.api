'use strict';

/**
 * A factory producing request mocks
 *
 * @param {string} url
 */
var RequestMockery = (function ($) {
    var mockFunctionNotCalled = 'The "mock" function has to be called before having access to the id of a request mock';
    var mockBeforeDestroy = 'The "destroy" function can not be called before the "mock" function has been called';

    var RequestMock = function (options, $) {
        this.options = options;
        this.$ = $;

        $.mockjaxSettings.throwUnmocked = true;
        $.mockjaxSettings.logging = false;
        $.mockjaxSettings.responseTime = 0;
    };

    /**
     * Mock an asynchronous request
     *
     * @returns {RequestMock}
     */
    RequestMock.prototype.mock = function () {
        this.id = this.$.mockjax(this.options);

        return this;
    };

    /**
     * Return the id of a request mock
     *
     * @returns {*}
     */
    RequestMock.prototype.getId = function () {
        if (this.id === undefined) {
            throw Error(mockFunctionNotCalled);
        }

        return this.id;
    };

    /**
     * Destroy a mocked request
     *
     * @returns {RequestMock}
     */
    RequestMock.prototype.destroy = function () {
        try {
            this.$.mockjax.clear(this.getId());
        } catch (error) {
           if (error === mockFunctionNotCalled) {
               throw Error(mockBeforeDestroy);
           } else {
               throw error;
           }
        }

        return this;
    };

    /**
     * Declare on after success callback
     *
     * @param url
     * @returns {RequestMock}
     */
    RequestMock.prototype.sendRequestToUrl = function (url) {
        this.options.url = url;

        return this;
    };

    /**
     * Set the response returned by the request
     *
     * @param response
     * @returns {RequestMock}
     */
    RequestMock.prototype.respondWith = function (response) {
        this.options.responseText = response;

        return this;
    };

    /**
     * Set on after success callback
     *
     * @param   {function} callback
     * @returns {RequestMock}
     */
    RequestMock.prototype.onAfterSuccess = function (callback) {
        this.options.onAfterSuccess = callback;

        return this;
    };

    /**
     * Set on after success callback
     *
     * @param   {function} callback
     * @returns {RequestMock}
     */
    RequestMock.prototype.onAfterError = function (callback) {
        if (callback === undefined) {
            this.options.onAfterError = function (error) {
                expect(error).toBeNull();
            };
        }

        return this;
    };

    /**
     * Set the HTTP method to use for the request to GET
     *
     * @returns {RequestMock}
     */
    RequestMock.prototype.shouldGet = function () {
        this.options.type = 'GET';

        return this;
    };

    /**
     * Set the HTTP method to use for the request to POST
     *
     * @returns {RequestMock}
     */
    RequestMock.prototype.shouldPost = function () {
        this.options.type = 'POST';

        return this;
    };

    return function (url) {
        return (new RequestMock({url: url}, $)).shouldGet();
    };
})(jQuery);
