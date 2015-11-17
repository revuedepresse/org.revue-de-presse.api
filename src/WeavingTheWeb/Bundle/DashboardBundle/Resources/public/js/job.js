function getJobsBoard($, eventListeners) {
    var jobsBoard = function ($, eventListeners) {
        var LOGGING_SEVERITY_LEVEL = {
            DEBUG: 10,
            ERROR: 40,
            INFO: 20,
            NONE: 50,
            WARN: 30
        };

        this.$ = $;
        this.debug = false;
        this.exceptions = {
            noContainerAvailable: 'No container available ' +
                'for listeners of event "{{ event_type }}".' + '\n' +
                '=> The event listener "container" property can be defined.',
            noEventEmittedOnSuccess: 'Missing event to be emitted ' +
                'on successful request for "{{ listener }}" listener.' + '\n' +
                '=> The event listener "request.emit" property can be defined.',
            noCustomEventHandlerAvailable: 'No custom event handler ' +
                'for "{{ event_type }}" event.' + '\n' +
                '=> "{{ method_name }}" function could be implemented.',
            noListenerAvailable: 'No listener available ' +
                'for "{{ event_type }}" event ' +
                'handled by "{{ listener }}" listener.' + '\n' +
                '=> The event listener "listeners" property can be defined.'
                + '\n' +
                '=> The event listener "after" property can be defined ' +
                'to ensure requirements have been met ' +
                'before attaching this event listener',
            suggestSelectorCheck: '=>  The "{{ selector }}" selector ' +
                'should match existing elements.',
            noRequest: 'Missing request for "{{ listener }}" listener' + '\n' +
                '=> The event listener "request" property can be defined',
            invalidLoggingLevel: 'An invalid logging level has been set ' +
                '("{{ logging_level }}").',
            invalidRequestUrl: 'Invalid request URL for ' +
                '"{{ event_type }}" event of "{{ listener }}" listener:' + '\n'
                + '=>  The event listener "request.url" property can be defined.'
                + '\n' +
                '=> The target listener "data-url" attribute can be defined.',
            unexpectedNodeName: 'Unexpected node name : "{{ actual }}" ' +
                '(expected: "{{ expected }}")'
        };
        this.info = {
            appendedListbox: 'Appended a listbox for listeners ' +
                'of "{{ event_type }}" events.',
            runPostEventHandler: 'About to run post event handler for ' +
                '"{{ event_type }}" event.',
            listenersUpdatedFromEvent: 'The "listeners" property ' +
                'has been updated for "{{ listener }}" event listener.',
            postEventHandlerUpdated: 'The post event handler has been updated '
                + 'for "{{ listener }}" event listener.'
        };
        if (!Array.isArray(eventListeners)) {
            throw 'Event listeners should be passed as an array ' +
                '(instance of "{{ type }}" given)'.replace(
                    '{{ type }}',
                    eventListeners.constructor.toString()
                );
        }

        this.eventListeners = eventListeners;
        this.onPostEventHandlers = {};

        this.dependencies = {};
        this.dependees = {};
        this.eventListenersByName = {};
        this.checkDependenciesBetweenListeners();

        this.logger = {};

        this.LOGGING_SEVERITY_LEVEL = LOGGING_SEVERITY_LEVEL;
        this.LOGGING_LEVEL = {};
        var loggingLevel;
        for (loggingLevel in this.LOGGING_SEVERITY_LEVEL) {
            this.LOGGING_LEVEL[loggingLevel] = loggingLevel;
        }
        this.loggingLevel = this.LOGGING_LEVEL.INFO;
        this.logFilter;
        this.injectLogger();
        this.promises = {};
        this.remote = 'http://localhost';
    };

    jobsBoard.prototype.checkDependenciesBetweenListeners = function () {
        var self = this;
        var $ = this.$;
        $.each(self.eventListeners, function (index, eventListener) {
            if (eventListener.after !== undefined) {
                self.dependencies[eventListener.name] = eventListener.after;
                self.dependees[eventListener.after] = eventListener.name;
            }

            self.eventListenersByName[eventListener.name] = eventListener;
        });
    };

    jobsBoard.prototype.shouldLog = function (logLevel) {
        return this.isLoggerActive(logLevel) && (
            this.logFilter === undefined ||
            this.logFilter === logLevel.toUpperCase()
        );
    };

    jobsBoard.prototype.injectLogger = function () {
        var nativeLogger = window.console;
        var self = this;
        if (nativeLogger) {
            var log;
            for (log in nativeLogger) {
                if (typeof nativeLogger[log].constructor == 'function') {
                    (function (logLevel) {
                        self.logger[logLevel] = function () {
                            var i;
                            var args = [];
                            for (i = 0; i < arguments.length; i++) {
                                args.push(arguments[i]);
                            }

                            if (self.shouldLog(logLevel)) {
                                nativeLogger[logLevel].apply(nativeLogger, args);
                            }
                        };
                    })(log);
                }
            }
        }
    };

    jobsBoard.prototype.logStackUnderCondition = function (condition) {
        if (condition) {
            this.logger.debug((new Error).stack);
        }

        return condition;
    };

    jobsBoard.prototype.setRemote = function (remote) {
        this.remote = remote;
    };

    jobsBoard.prototype.isLoggerActive = function (logger) {
        var logLevelForLogger = logger.toUpperCase();
        if (this.debug) {
            return this.getLoggingCriticityLevel(logLevelForLogger) >=
                this.getLoggingCriticityLevel(this.loggingLevel);
        }
    };

    jobsBoard.prototype.validateLoggingLevel = function (level) {
        if (level !== undefined) {
            if (this.LOGGING_LEVEL[level] !== undefined) {
                return level;
            } else {
                if (window.console && window.console.debug) {
                    // Prevent infinite looping
                    window.console.debug(this.exceptions.invalidLoggingLevel
                        .replace('{{ logging_level }}', level));
                }
            }
        }

        return this.LOGGING_LEVEL.NONE;
    };

    jobsBoard.prototype.getLoggingCriticityLevel = function (level) {
        return this.LOGGING_SEVERITY_LEVEL[
            this.validateLoggingLevel(level)
        ];
    };

    jobsBoard.prototype.enableDebug = function () {
        this.debug = true;

        return this;
    };

    jobsBoard.prototype.filterLogByLevel = function (level) {
        this.logFilter = level;
    };

    jobsBoard.prototype.setLoggingLevel = function (level) {
        this.loggingLevel = this.validateLoggingLevel(level);

        return this;
    };

    jobsBoard.prototype.disableDebug = function () {
        this.debug = false;

        return this;
    };

    jobsBoard.prototype.addEventListener = function (listener) {
        this.eventListeners.push(listener);

        return this;
    };

    jobsBoard.prototype.validateRequest = function (request) {
        var self = this;
        var $ = self.$;
        var invalidRequestMethod;

        if (request.method === undefined) {
            throw 'The request method should be explicitly defined.';
        }

        if (!$[request.method]) {
            invalidRequestMethod = 'Invalid HTTP request method ("{{ method }}")'
                .replace('{{ method }}', request.method);
            throw invalidRequestMethod;
        }

        return request;
    };

    jobsBoard.prototype.ensureBindingHasName = function (binding) {
        if (!binding.name) {
            throw 'Missing name for event listener';
        }
    };

    jobsBoard.prototype.shouldEmitEvent = function (request) {
        return request && request.success.emit;
    };

    jobsBoard.prototype.updateRequestHeaders = function (request) {
        var headers = {};
        var headerIndex;
        var header;

        if (request.headers) {
            var bindingHeaders = request.headers;
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

        request.getHeaders = function () {
            return headers;
        };
    };

    jobsBoard.prototype.getPromiseId = function (request) {
        var requestParent = request.sender.parents('[data-entity]');
        var promiseId;

        if (requestParent.length > 0) {
            promiseId = [
                requestParent.attr('data-entity'),
                requestParent.attr('data-id'),
                request.binding.name
            ].join('-');
        } else {
            promiseId = request.binding.name;
        }

        return promiseId;
    };

    jobsBoard.prototype.makePromise = function (request) {
        if (request.binding.promises === undefined) {
            request.binding.promises = {};
        }
        var promiseId = this.getPromiseId(request);
        request.binding.promises[promiseId] = this.promises[promiseId];
    };

    jobsBoard.prototype.remindPromise = function (request) {
        var promiseId = this.getPromiseId(request);

        return request.binding.promises[promiseId];
    };

    jobsBoard.prototype.makeXhrPromise = function (request) {
        var $ = this.$;
        var ajaxSettings = {
            url: request.getUrl(),
            headers : request.getHeaders(),
            success: request.binding.success,
            type: request.getMethod()
        };

        var promiseId = this.getPromiseId(request);
        this.promises[promiseId] = $.ajax(ajaxSettings);
        this.makePromise(request);

        return this.promises[promiseId];
    };

    jobsBoard.prototype.byEventType = function (type) {
        var self = this;
        var eventType = type;
        return function (listener) {
            if (listener.type == eventType) {
                if (listener.container !== undefined) {
                    if (listener.container.length == 0) {
                        self.logger.error(self.exceptions.noContainerAvailable
                            .replace('{{ event_type }}', eventType));
                    }
                    var listBox = $('<table />', {
                        'data-listen-event': eventType,
                        'class': 'table table-condensed'
                    });
                    listener.container.append(listBox);
                    self.logger.info(self.info.appendedListbox
                        .replace('{{ event_type }}', eventType));
                }
            }
        };
    };

    jobsBoard.prototype.getListenersSelector = function (eventType) {
        if (
          eventType === undefined ||
          eventType.constructor.toString().indexOf('String') === -1
        ) {
            throw 'Invalid event type';
        }

        return '[data-listen-event="{{ event_type }}"]'
            .replace('{{ event_type }}', eventType);
    };

    jobsBoard.prototype.onOkResponse = function (request) {
        var promise = this.remindPromise(request);
        var self = this;

        if (this.shouldEmitEvent(request)) {
            promise.done(function (data) {
                var eventType = request.success.emit;
                var target = self.getOnSuccessTarget(request);
                var event = $.Event(eventType);
                data.emitter = request.binding.name;
                target.trigger(event, data);
            });
        }
    };

    jobsBoard.prototype.isFunction = function (subject) {
        return subject && (typeof subject == 'function');
    };

    jobsBoard.prototype.isTable = function (node) {
        return node.nodeName === 'TABLE';
    };

    jobsBoard.prototype.shouldFormatColumn = function (name) {
        return name.indexOf('rlk_') == 0;
    };

    jobsBoard.prototype.formatColumnName = function (subject) {
        if (this.shouldFormatColumn(subject)) {
            return subject.substring(4);
        } else {
            return subject;
        }
    };

    jobsBoard.prototype.prePopulateTable = function (table, columns) {
        if ($(table).find('thead').length === 0) {
            var tableHead = $('<thead />');
            var columnIndex;
            $(table).append(tableHead);

            if (columns !== undefined) {
                var headRow = $('<tr />');
                for (
                    columnIndex = 0;
                    columnIndex < columns.length;
                    columnIndex++
                ) {
                    var columnName = columns[columnIndex];
                    if (!this.isColumnHidden(columnName)) {
                        columnName = this.formatColumnName(columnName);
                        headRow.append($('<td>', {text: columnName}));
                    }
                }

                tableHead.append(headRow);
            }
        }

        if ($(table).find('tbody').length === 0) {
            var tableBody = $('<tbody />');
            $(table).append(tableBody);

        }

        return table;
    };

    jobsBoard.prototype.appendRow = function (target, source) {
        var row = this.makeRow(source);

        if (this.isTable(target)) {
            var table = this.prePopulateTable(target, Object.keys(source));
            $(table).find('tbody').append(row);
        } else {
            throw this.exceptions.unexpectedNodeName
                .replace('{{ actual }}', target.nodeName)
                .replace('{{ expected }}', 'TABLE');
        }
    };

    /**
     * @see
     * http://stackoverflow.com/a/2117523/282073
     * http://tools.ietf.org/html/rfc4122
     */
    jobsBoard.prototype.generateUuid = function () {
        return uuid.v1();
    };

    jobsBoard.prototype.isColumnHidden = function (name) {
        return name === 'entity' || name === 'id';
    };

    jobsBoard.prototype.formatColumnValue = function (subject, columnName) {
        var formattedSubject = subject;

        if (this.shouldFormatColumn(columnName)) {
            if (subject !== null && subject.length > 0) {
                var parts = subject.split('/');
                formattedSubject = $('<a />', {
                    'class': 'btn',
                    'data-action': 'download-job-archive',
                    'data-url': this.remote + subject,
                    text: parts[parts.length - 1]
                });
            } else {
                formattedSubject = '';
            }
        }

        return formattedSubject;
    };

    jobsBoard.prototype.makeRow = function (columns) {
        var self = this;
        var $ = self.$;
        var entity;
        var id;

        if (columns.entity !== undefined) {
            entity = columns.entity;
        } else {
            entity = 'generic-entity';
        }

        if (columns.id !== undefined) {
            id = columns.id;
        } else {
            id = this.generateUuid;
        }
        var row = $('<tr />', {
            'data-entity': entity,
            'data-id': id
        });
        $.each(columns, function (columnName, columnValue) {
            if (!self.isColumnHidden(columnName)) {
                var column = $('<td>', {
                    'data-column-name': self.formatColumnName(columnName)
                });
                var formattedColumnValue = self.formatColumnValue(
                    columnValue,
                    columnName
                );
                columnValue = formattedColumnValue;
                if (formattedColumnValue instanceof $) {
                    columnValue = formattedColumnValue[0];
                }
                column.append(columnValue);
                row.append(column);
            }
        });

        return row;
    };

    jobsBoard.prototype.onJobsCreated = function (event) {
        var self = this;
        var $ = self.$;

        $.each(event.target, function (targetIndex, target) {
            self.appendRow(target, event.data.job);
        });
    };

    jobsBoard.prototype.onJobsListed = function (event) {
        var data = event.data;
        var self = this;

        $.each(event.target, function (targetIndex, target) {
            $.each(data.collection, function (jobIndex, job) {
                self.appendRow(target, job);
            });
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

    jobsBoard.prototype.ensureRequestHasMethod = function (request) {
        if (request.method === undefined) {
            request.method = 'get';
        }

        request.getMethod = function () {
            return request.method;
        };
    };

    jobsBoard.prototype.ensureRequestSenderHasUrl = function (request) {
        var binding = request.binding;
        var listener = {
            eventType: binding.type,
            name: binding.name
        };

        if (
            request.url === undefined &&
            request.sender.attr('data-url') === undefined
        ) {
            throw this.exceptions.invalidRequestUrl
                .replace('{{ event_type }}', listener.eventType)
                .replace('{{ listener }}', listener.name);
        }
    };

    jobsBoard.prototype.ensureRequestHasUrl = function (request) {
        var getter;

        this.ensureRequestSenderHasUrl(request);

        if (
          request.url === undefined &&
          request.sender.attr('data-url') !== undefined
        ) {
            getter = function () {
                return request.sender.attr('data-url');
            };
        } else {
            getter = function () {
                return request.url;
            };
        }

        request.getUrl = getter;
    };

    jobsBoard.prototype.bindNativeEventHandlers = function (binding) {
        var self = this;
        var onEvent = function () {
            var request;

            if (binding.request !== undefined) {
                request = binding.request;
                self.appendListboxes(request);
                request.binding = binding;
            } else {
                throw new Error(self.exceptions.noRequest
                    .replace('{{ listener }}', binding.name));
            }

            return function () {
                request.sender = $(this);

                self.ensureRequestHasUrl(request);
                self.ensureRequestHasMethod(request);

                self.updateRequestHeaders(request);

                request = self.validateRequest(request);

                var promise = self.makeXhrPromise(request);
                self.onOkResponse(request);

                return promise;
            };
        };

        $.each(binding.listeners, function (listenerIndex, listener) {
            var eventListener = $(listener);
            var eventSubscriber = self.getEventSubscriber(
                eventListener,
                binding.type
            );

            // Re-bind the event subscriber to the listener
            eventSubscriber = eventSubscriber.bind(eventListener);

            // Detaches previously attached handler from the listeners
            eventListener.off(binding.type);

            // Re-bind the handler to the listener
            onEvent = onEvent.bind(eventListener);

            eventSubscriber(onEvent());
        });

        self.updateNextBinding(binding);
        if (binding.then) {
            binding.then();
            self.handlePostEvent(binding);
        }
    };

    jobsBoard.prototype.shouldHandlePostEvent = function (binding) {
        return this.isFunction(binding.onPostEventHandler);
    };

    jobsBoard.prototype.updateBindingListeners = function (binding) {
        if (binding.listeners === undefined) {
            var listenersSelector = this.getListenersSelector(binding.type);
            var listeners = $(listenersSelector);
            if (listeners.length === 0) {
                this.logger.debug(this.exceptions.noListenerAvailable
                    .replace('{{ event_type }}', binding.type)
                    .replace('{{ listener }}', binding.name))
                    .replace('{{ selector }}', listenersSelector);
            }
            this.logger.info(this.info.listenersUpdatedFromEvent
                .replace('{{ listener }}', binding.name));

            binding.listeners = listeners;
        }
    };

    jobsBoard.prototype.updateNextBinding = function (binding, customEvent) {
        var self = this;

        if (this.hasDependees(binding)) {
            var willBindEventListener = function () {
                var dependee = self.getBindingDependees(binding);
                self.bindEvent(self.eventListenersByName[dependee]);
            };

            if (customEvent !== undefined) {
                customEvent.then = willBindEventListener;
            } else {
                binding.then = willBindEventListener;
            }
        }
    };

    jobsBoard.prototype.getBindingDependees = function (binding) {
        return this.dependees[binding.name];
    };

    jobsBoard.prototype.hasDependees = function (binding) {
        if (this.dependees[binding.name] === undefined) {
            return false;
        }
        var dependee = this.getBindingDependees(binding);

        return this.eventListenersByName[dependee] !== undefined;
    };

    jobsBoard.prototype.handlePostEvent = function (binding, customEvent) {
        if (this.shouldHandlePostEvent(binding)) {
            if (customEvent === undefined && this.isFunction(binding.then)) {
                customEvent = {
                    data: {
                        emitter: binding.name
                    },
                    then: binding.then
                };
            }

            this.logger.info(this.info.runPostEventHandler
                .replace('{{ event_type }}', binding.name));
            binding.onPostEventHandler(customEvent);
            delete binding.onPostEventHandler;
        }
    };

    jobsBoard.prototype.bindCustomEventHandlers = function (binding) {
        var self = this;
        self.updateBindingListeners(binding);

        binding.listeners.on(binding.type, function (event, data) {
            event.stopPropagation();

            var customEvent = {
                data: data,
                target: binding.listeners
            };
            self.updateNextBinding(binding, customEvent);

            var handlerName;
            var handler;

            if (!self.isFunction(binding.handler)) {
                handlerName = self.getHandlerName(binding.type);
                if (self[handlerName] && self.isFunction(self[handlerName])) {
                    handler = self[handlerName].bind(self);
                    handler(customEvent);
                } else {
                    self.logger.debug(
                        self.exceptions.noCustomEventHandlerAvailable
                        .replace('{{ event_type }}', binding.type)
                        .replace('{{ method_name }}', handlerName)
                    );
                }

                self.handlePostEvent(binding, customEvent);

                return;
            }

            if (self.shouldHandlePostEvent(binding)) {
                binding.handler(customEvent, binding.onPostEventHandler);
            } else {
                binding.handler(customEvent);
            }
        });
    };

    jobsBoard.prototype.getOnSuccessTarget = function (request) {
        var self = this;
        if (self.debug) {
            if (request.success.emit === undefined) {
                self.logger.debug(self.exceptions.noEventEmittedOnSuccess
                    .replace('{{ listener }}', request.binding.name));
            }
        }
        var eventType = request.success.emit;
        var listenersSelector = self.getListenersSelector(eventType);
        return self.$(listenersSelector);
    };

    jobsBoard.prototype.appendListboxes = function (request) {
        var target = this.getOnSuccessTarget(request);
        if (!target || target.length === 0) {
            var eventType = request.success.emit;
            var appendListToContainer = this.byEventType(eventType)
                .bind(self);
            this.eventListeners.map(appendListToContainer);
        }
    };

    jobsBoard.prototype.getEventSubscriber = function (listener, eventType) {
        return listener[eventType];
    };

    jobsBoard.prototype.getNativeEventSubscriber = function (binding) {
        return this.getEventSubscriber(binding.listeners, binding.type);
    };

    jobsBoard.prototype.areListenersDeclared = function (binding) {
        return binding.listeners && binding.type;
    };

    jobsBoard.prototype.areListenersAvailable = function (binding) {
        this.refreshListenersSelection(binding);
        return binding.listeners.length > 0;
    };

    jobsBoard.prototype.refreshListenersSelection = function (binding) {
        if (binding.listeners && binding.listeners.selector) {
            binding.listeners = $(binding.listeners.selector);
        }
    };

    jobsBoard.prototype.isNativeEvent = function (binding) {
        var isNativeEvent;
        if (this.areListenersDeclared(binding)) {
            var eventSubscriber = this.getNativeEventSubscriber(binding);
            isNativeEvent = typeof eventSubscriber === 'function';
            if (!this.areListenersAvailable(binding)) {
                throw new Error(this.exceptions.noListenerAvailable
                    .replace('{{ event_type }}', binding.type)
                    .replace('{{ listener }}', binding.name) + '\n' +
                    this.exceptions.suggestSelectorCheck
                        .replace('{{ selector }}', binding.listeners.selector));
            }
        } else {
            isNativeEvent = false;
        }
        this.logger.debug('Event for binding "{{ name }}" is {{ ? }}native.'
            .replace('{{ name }}', binding.name)
            .replace('{{ ? }}', isNativeEvent ? '' : 'not '));

        return isNativeEvent;
    };

    jobsBoard.prototype.bindEvent = function (eventListener, success) {
        var self = this;
        var binding = eventListener;

        binding.success = success || function () {};
        var declaredListeners = self.areListenersDeclared(binding);
        if (declaredListeners || binding.container) {
            var nativeEvent = self.isNativeEvent(binding);
            if (nativeEvent) {
                self.bindNativeEventHandlers(binding);
            } else {
                self.bindCustomEventHandlers(binding);
            }
        } else if (!declaredListeners && binding.container == undefined) {
            var checkSelector = '';
            if (declaredListeners) {
                checkSelector = self.exceptions.suggestSelectorCheck
                    .replace('{{ selector }}', binding.listeners.selector);
            }

            throw new Error(self.exceptions.noContainerAvailable
                .replace('{{ event_type }}', binding.type) + '\n' +
                self.exceptions.noListenerAvailable
                .replace('{{ event_type }}', binding.type)
                .replace('{{ listener }}', binding.name) + checkSelector);
        }
    };

    jobsBoard.prototype.promisingEmitter = function (emitter) {
        return this.promises[emitter] !== undefined;
    };

    jobsBoard.prototype.doNextBinding = function (customEvent) {
        if (customEvent !== undefined && this.isFunction(customEvent.then)) {
            var emitter = customEvent.data.emitter;
            if (emitter !== undefined && this.promisingEmitter(emitter)) {
                this.promises[emitter].always(customEvent.then);
            } else {
                customEvent.then();
            }
        }
    };

    jobsBoard.prototype.updateOnPostEventHandler = function (listener) {
        var self = this;

        listener.onPostEventHandler = function () {
            // Ensures post event can be used to manage dependencies
            // between event listeners by using "after" property
            if (typeof arguments[0] !== undefined) {
                self.doNextBinding(arguments[0]);
            }

            if (
                self.onPostEventHandlers &&
                self.onPostEventHandlers[listener.name]
            ) {
                self.onPostEventHandlers[listener.name].apply(self);
            }
        };

        self.logger.info(
            self.info.postEventHandlerUpdated
            .replace('{{ listener }}', listener.name)
        );
    };

    jobsBoard.prototype.mount = function (onPostEventHandlers) {
        var self = this;
        var totalListeners = this.eventListeners.length;
        var listenerIndex;
        var listener;

        self.onPostEventHandlers = onPostEventHandlers;

        for (
          listenerIndex = 0;
          listenerIndex < totalListeners;
          listenerIndex++
        ) {
            listener = this.eventListeners[listenerIndex];
            this.ensureBindingHasName(listener);
            this.updateOnPostEventHandler(listener);
            if (this.dependencies[listener.name] === undefined) {
                self.bindEvent(listener);
            }
        }
    };

    return new jobsBoard($, eventListeners);
}

if (window.jQuery && window.Routing) {
    var container = $('[data-container="jobs-board"]');
    var routing = window.Routing;
    var schemeHost = $('[data-request-prop="scheme-host"]').val();
    var header = $('[data-request-prop="header"]');
    var headers = [{
        key: header.attr('data-key'),
        value: header.val()
    }];

    var createdJobEventType = 'jobs:created';
    var exportPerspectivesButtons = $('[data-action="export-perspectives"]');
    var exportPerspectivesUrl = schemeHost +
        routing.generate('weaving_the_web_api_export_perspectives');

    var listJobsUrl = schemeHost +
        routing.generate('weaving_the_web_api_get_jobs');
    var listedJobEventType = 'jobs:listed';

    var downloadedJobArchiveEvent = 'job:archive:downloaded';
    var downloadJobArchiveButtons = $('[data-action="download-job-archive"]');
    var saveJobArchiveButton = $('[data-action="save-job-archive"]');

    var eventListeners = [{
        listeners: exportPerspectivesButtons,
        name: 'export-perspectives',
        request: {
            url: exportPerspectivesUrl,
            method: 'post',
            headers: headers,
            success: {
                emit: createdJobEventType
            }
        },
        type: 'click'
    }, {
        listeners: $('body'),
        name: 'list-jobs',
        request: {
            url: listJobsUrl,
            headers: headers,
            success: {
                emit: listedJobEventType
            }
        },
        type: 'load'
    }, {
        container: container,
        name: 'post-job-creation',
        type: createdJobEventType
    }, {
        container: container,
        name: 'post-job-listing',
        type: listedJobEventType
    }, {
        after: 'post-job-listing',
        listeners: downloadJobArchiveButtons,
        name: 'download-job-archive',
        request: {
            headers: headers,
            success: {
                emit: downloadedJobArchiveEvent
            }
        },
        type: 'click'
    }, {
        listeners: saveJobArchiveButton,
        name: 'post-job-archive-download',
        type: downloadedJobArchiveEvent
    }];

    var jobsBoard = getJobsBoard($, eventListeners);
    jobsBoard.enableDebug();
    jobsBoard.setRemote(schemeHost);
    jobsBoard.mount();

    $('body').load();
}
