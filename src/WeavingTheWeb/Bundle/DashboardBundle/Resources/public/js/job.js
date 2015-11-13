function getJobsBoard($, eventListeners) {
    var jobsBoard = function ($, eventListeners) {
        this.$ = $;
        this.eventListeners = eventListeners;
        this.promises = {};
    };

    jobsBoard.prototype.validateRequest = function (request) {
        var self = this;
        var $ = self.$;
        var invalidRequestMethod;

        if (!request.method) {
            throw 'The request method should be explicitly defined.';
        }

        if (!$[request.method]) {
            invalidRequestMethod = 'Invalid HTTP request method ("{{ method }}")'
                .replace('{{ method }}', request.method);
            throw invalidRequestMethod;
        }
    };

    jobsBoard.prototype.ensureBindingHasName = function (binding) {
        if (!binding.name) {
            throw 'Missing name for event listener';
        }
    };

    jobsBoard.prototype.shouldEmitEvent = function (request) {
        return request && request.success.emit;
    };

    jobsBoard.prototype.validateNativeEventHandling = function (binding) {
        if (binding.request) {
            this.validateRequest(binding.request);
        }

        return binding.request;
    };

    jobsBoard.prototype.makeXhrPromise = function (binding) {
        var $ = this.$;
        var request = this.validateNativeEventHandling(binding);
        var headers = {};
        var headerIndex;
        var header;

        if (binding.request.headers) {
            var bindingHeaders = binding.request.headers;
            for (
              headerIndex = 0;
              headerIndex < bindingHeaders.length;
              headerIndex++
            ) {
                header = bindingHeaders[headerIndex];
                if (header.key === undefined) {
                    throw 'Invalid header key';
                }
                if (!header.value === undefined) {
                    throw 'Invalid header value for key "{{ name}})"'
                        .replace('{{ name }}', header.key);
                }
                headers[header.key] = header.value;
            }
        }
        var ajaxSettings = {
            url: request.uri,
            headers : headers,
            success: binding.success,
            type: binding.request.method
        };
        this.promises[binding.name] = $.ajax(ajaxSettings);

        return this.promises[binding.name];
    };

    jobsBoard.prototype.onOkResponse = function (binding) {
        var promise = this.promises[binding.name];
        var request = binding.request;
        var self = this;

        if (this.shouldEmitEvent(request)) {
            promise.done(function (data) {
                var eventType = request.success.emit;
                var listenersSelector = '[data-listen-event="{{ event_type }}"]'
                  .replace('{{ event_type }}', eventType);
                var target = self.$(listenersSelector);
                var event = $.Event(eventType);
                target.trigger(event, data);
            });
        }
    };

    jobsBoard.prototype.isFunction = function (subject) {
        return subject && (typeof subject == 'function');
    };

    jobsBoard.prototype.onJobCreated = function (event) {
        var self = this;
        var $ = self.$;
        var data = event.data;
        var item = $('<li />', {
            'data-job-id': data.job.id,
            'text': data.status
        });
        $.each(event.target, function (targetIndex, target) {
            $(target).append(item);
        });
    };

    jobsBoard.prototype.getHandlerName = function (eventType) {
        var capitalizedFirstChar;
        var loweredRest;
        var parts = eventType.split(':');
        var partIndex;
        var part;
        var words = [];

        for (partIndex = 0; partIndex < parts.length; partIndex++) {
            part = parts[partIndex];
            capitalizedFirstChar = part.substr(0, 1).toUpperCase();
            loweredRest = part.substr(1, part.length).toLowerCase();
            words.push(capitalizedFirstChar + loweredRest);
        }

        return 'on' + words.join('');
    };

    jobsBoard.prototype.bindCustomEvents = function (binding) {
        var self = this;

        binding.listeners.on(binding.type, function (event, data) {
            var onPostEventHandler;
            var shouldHandlePostEvent = self.isFunction(
                binding.onPostEventHandler
            );
            var handlerName;
            var handler;

            event.stopPropagation();

            var customEvent = {
                data: data ,
                target: binding.listeners
            };

            if (shouldHandlePostEvent) {
                onPostEventHandler = binding.onPostEventHandler;
            }

            if (!self.isFunction(binding.handler)) {
                handlerName = self.getHandlerName(binding.type);
                if (self[handlerName] && self.isFunction(self[handlerName])) {
                    handler = self[handlerName].bind(self);
                    handler(customEvent);
                }

                onPostEventHandler();
                return;
            }

            if (shouldHandlePostEvent) {
                binding.handler(customEvent, onPostEventHandler);
            } else {
                binding.handler(customEvent);
            }
        });
    };

    jobsBoard.prototype.bindEvent = function (eventListener, success) {
        var self = this;
        var binding = eventListener;
        var onEvent;

        binding.success = success || function () {};

        if (binding.listeners && binding.type) {
            var event = binding.listeners[binding.type];
            var nativeEvent = typeof event === 'function';
            if (nativeEvent) {
                // Re-bind the handler to the listeners
                event = event.bind(binding.listeners);

                onEvent = function (request) {
                    if (request && request.method) {
                        return function () {
                            var promise = self.makeXhrPromise(binding);
                            self.onOkResponse(binding);

                            return promise;
                        };
                    }
                };
                event(onEvent(binding.request));
            } else {
                self.bindCustomEvents(binding);
            }
        }
    };

    jobsBoard.prototype.mount = function (onPostEventHandlers) {
        var self = this;
        var totalListeners = this.eventListeners.length;
        var listenerIndex;
        var listener;

        for (
          listenerIndex = 0;
          listenerIndex < totalListeners;
          listenerIndex++
        ) {
            listener = this.eventListeners[listenerIndex];
            this.ensureBindingHasName(listener);
            if (onPostEventHandlers && onPostEventHandlers[listener.name]) {
                listener.onPostEventHandler = onPostEventHandlers[listener.name];
            }
            self.bindEvent(listener);
        }
    };

    return new jobsBoard($, eventListeners);
}

if (window.jQuery && window.Routing) {
    var routing = window.Routing;
    var exportPerspectivesButtons = $('[data-action="export-perspectives"]');
    var jobCreatedListeners = $('[data-listen-event="job:created"]');
    var schemeHost = $('[data-request-prop="scheme-host"]').val();
    var header = $('[data-request-prop="header"]');
    var uri = schemeHost +
        routing.generate('weaving_the_web_api_export_perspectives');

    var eventListeners = [{
        name: 'export-perspectives',
        type: 'click',
        listeners: exportPerspectivesButtons,
        request: {
            uri: uri,
            method: 'post',
            headers: [
                {
                    key: header.attr('data-key'),
                    value: header.val()
                }
            ],
            success: {
                emit: 'job:created'
            }
        }
    }, {
        name: 'post-job-creation',
        type: 'job:created',
        listeners: jobCreatedListeners
    }];

    window.jobsBoard = getJobsBoard($, eventListeners);
    window.jobsBoard.mount();
}
