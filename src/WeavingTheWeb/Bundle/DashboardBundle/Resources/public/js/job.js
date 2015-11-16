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
                'for listeners of event "{{ event_type }}".',
            noEventEmittedOnSuccess: 'Missing event to be emitted ' +
                'on successful request for listener "{{ listener }}". ' +
                'Maybe the "request.emit" property should be defined?' ,
            noCustomEventHandlerAvailable: 'No custom event handler ' +
                'for event "{{ event_type }}". ' +
                'Please implement method "{{ method_name }}".',
            noListenersAvailable: 'No listeners available ' +
                'for event "{{ event_type }}".',
            invalidLoggingLevel: 'An invalid logging level has been set ' +
                '("{{ logging_level }}").',
            unexpectedNodeName: 'Unexpected node name : "{{ actual }}" ' +
                '(expected: "{{ expected }}")'
        };
        this.info = {
            appendedListbox: 'Appended a listbox for listeners ' +
                'of "{{ event_type }}" events'
        };
        if (!Array.isArray(eventListeners)) {
            throw 'Event listeners should be passed as an array ' +
                '(instance of "{{ type }}" given)'.replace(
                    '{{ type }}',
                    eventListeners.constructor.toString()
                );
        }
        this.eventListeners = eventListeners;
        this.logger = {};

        this.LOGGING_SEVERITY_LEVEL = LOGGING_SEVERITY_LEVEL;
        this.LOGGING_LEVEL = {};
        var loggingLevel;
        for (loggingLevel in this.LOGGING_SEVERITY_LEVEL) {
            this.LOGGING_LEVEL[loggingLevel] = loggingLevel;
        }
        this.loggingLevel = this.LOGGING_LEVEL.INFO;
        this.injectLogger();
        this.promises = {};
        this.remote = 'http://localhost';
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

                            if (self.isLoggerActive(logLevel)) {
                                nativeLogger[logLevel].apply(nativeLogger, args);
                            }
                        };
                    })(log);
                }
            }
        }
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

    jobsBoard.prototype.onOkResponse = function (binding) {
        var promise = this.promises[binding.name];
        var request = binding.request;
        var self = this;

        if (this.shouldEmitEvent(request)) {
            promise.done(function (data) {
                var eventType = request.success.emit;
                var target = self.getOnSuccessTarget(binding);
                var event = $.Event(eventType);
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
                    'data-action': 'download-archive',
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

    jobsBoard.prototype.onJobCreated = function (event) {
        var self = this;
        var $ = self.$;

        $.each(event.target, function (targetIndex, target) {
            self.appendRow(target, event.data.job);
        });
    };

    jobsBoard.prototype.onJobListed = function (event) {
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

    jobsBoard.prototype.bindNativeEventHandlers = function (binding) {
        var self = this;
        var onEvent = function (request) {
            if (request.method === undefined) {
                request.method = 'get';
            }
            self.appendListboxes(binding);

            return function () {
                var promise = self.makeXhrPromise(binding);
                self.onOkResponse(binding);

                return promise;
            };
        };

        var eventHandler = self.getNativeEventHandler(binding);
        // Re-bind the handler to the listeners
        eventHandler = eventHandler.bind(binding.listeners);
        eventHandler(onEvent(binding.request));
    };

    jobsBoard.prototype.shouldHandlePostEvent = function (binding) {
        return this.isFunction(binding.onPostEventHandler);
    };

    jobsBoard.prototype.bindCustomEventHandlers = function (binding) {
        var self = this;

        if (binding.listeners === undefined) {
            var listenersSelector = this.getListenersSelector(binding.type);
            var listeners = $(listenersSelector);
            if (listeners.length === 0) {
                this.logger.debug(this.exceptions.noListenersAvailable
                    .replace('{{ event_type }}',  binding.type));
            }

            binding.listeners =  listeners;
        }

        binding.listeners.on(binding.type, function (event, data) {
            var handlerName;
            var handler;

            event.stopPropagation();

            var customEvent = {
                data: data,
                target: binding.listeners
            };

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

                if (self.shouldHandlePostEvent(binding)) {
                    binding.onPostEventHandler();
                }

                return;
            }

            if (self.shouldHandlePostEvent(binding)) {
                binding.handler(customEvent, binding.onPostEventHandler);
            } else {
                binding.handler(customEvent);
            }
        });
    };

    jobsBoard.prototype.getOnSuccessTarget = function (binding) {
        var self = this;
        var request = binding.request;
        if (self.debug) {
            if (request.success.emit === undefined) {
                self.logger.debug(self.exceptions.noEventEmittedOnSuccess
                    .replace('{{ listener }}', binding.name));
            }
        }
        var eventType = request.success.emit;
        var listenersSelector = self.getListenersSelector(eventType);
        return self.$(listenersSelector);
    };

    jobsBoard.prototype.appendListboxes = function (binding) {
        var target = this.getOnSuccessTarget(binding);
        var request = binding.request;
        if (!target || target.length === 0) {
            var eventType = request.success.emit;
            var appendListToContainer = this.byEventType(eventType)
                .bind(self);
            this.eventListeners.map(appendListToContainer);
        }
    };

    jobsBoard.prototype.getNativeEventHandler = function (binding) {
        return binding.listeners[binding.type];
    };

    jobsBoard.prototype.areListenersDeclared = function (binding) {
        return binding.listeners && binding.type;
    };

    jobsBoard.prototype.isNativeEvent = function (binding) {
        var isNativeEvent;
        if (this.areListenersDeclared(binding)) {
            var eventHandler = this.getNativeEventHandler(binding);
            isNativeEvent = typeof eventHandler === 'function';
        } else {
            isNativeEvent = false;
        }
        this.logger.info('Event for binding "{{ name }}" is {{ ? }}native.'
            .replace('{{ name }}', binding.name)
            .replace('{{ ? }}', isNativeEvent ? '' : 'not '));

        return isNativeEvent;
    };

    jobsBoard.prototype.bindEvent = function (eventListener, success) {
        var self = this;
        var binding = eventListener;

        binding.success = success || function () {};
        if (self.areListenersDeclared(binding) || binding.container) {
            var nativeEvent = self.isNativeEvent(binding);
            if (nativeEvent) {
                self.bindNativeEventHandlers(binding);
            } else {
                self.bindCustomEventHandlers(binding);
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
    var schemeHost = $('[data-request-prop="scheme-host"]').val();
    var header = $('[data-request-prop="header"]');
    var exportPerspectivesUri = schemeHost +
        routing.generate('weaving_the_web_api_export_perspectives');
    var listJobsUri = schemeHost +
        routing.generate('weaving_the_web_api_get_jobs');
    var headers = [{
        key: header.attr('data-key'),
        value: header.val()
    }];
    var createdJobEventType = 'job:created';
    var listedJobEventType = 'job:listed';
    var container = $('[data-container="jobs-board"]');
    var eventListeners = [{
        name: 'export-perspectives',
        type: 'click',
        listeners: exportPerspectivesButtons,
        request: {
            uri: exportPerspectivesUri,
            method: 'post',
            headers: headers,
            success: {
                emit: createdJobEventType
            }
        }
    }, {
        name: 'list-jobs',
        type: 'load',
        listeners: $('body'),
        request: {
            uri: listJobsUri,
            headers: headers,
            success: {
                emit: listedJobEventType
            }
        }
    }, {
        container: container,
        name: 'post-job-creation',
        type: createdJobEventType
    }, {
        container: container,
        name: 'post-job-listing',
        type: listedJobEventType
    }];

    var jobsBoard = getJobsBoard($, eventListeners);
    jobsBoard.setRemote(schemeHost);
    jobsBoard.mount();

    $('body').load();
}
