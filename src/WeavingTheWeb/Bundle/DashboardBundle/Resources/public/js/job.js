(function (exports, require) {
    var uuid = require.uuid;
    var logger = require.logger;

    exports.getJobsBoard = function ($, eventListeners) {
        var jobsBoard = function ($, eventListeners) {
            this.DEFAULT_POLLING_INTERVAL_DELAY = 15000;
            this.$ = $;
            this.debug = false;
            this.exceptions = {
                mismatchingEntity: 'There is a mismatch between ' +
                    'the "entity" property ("{{ entity }}") of '
                    + 'the response content '
                    + 'and the entity ("{{ target_entity }}") targeted ' +
                    'by "{{ emitter }}" emitter ',
                noColumnAvailable: 'No "{{ column }}" column ' +
                    'for "{{ entity }}" target with id #{{ id }}.',
                noContainerAvailable: 'No container available ' +
                    'for listeners of event "{{ event_type }}".' + '\n' +
                    '=> The event listener "container" property can be defined.',
                noContentAvailable: 'No content available.',
                noResponseEntity: 'No entity available in data propagated by ' +
                    '"{{ emitter }}" emitter.' +
                    '\n' + '=> An "entity" property is expected ' +
                    'in the response content.',
                noTargetEntity: 'No target entity for "{{ emitter }}" emitter.' +
                    '\n' + '=> the target listener "data-entity" attribute ' +
                    'can be defined.',
                noRequest: 'Missing request for "{{ listener }}" listener' +
                    '\n' +
                    '=> The event listener "request" property can be defined',
                noEventEmittedOnSuccess: 'Missing event to be emitted ' +
                    'on successful request for "{{ listener }}" listener.' + '\n' +
                    '=> The event listener "request.emit" property can be defined.',
                noCustomEventHandlerAvailable: 'No custom event handler ' +
                    'for "{{ event_type }}" event.' + '\n' +
                    '=> "{{ method_name }}" function could be implemented.',
                noListenerAvailable: 'No listener available ' +
                    'for "{{ event_type }}" event ' +
                    'handled by "{{ listener }}" listener.',
                notImplemented: 'Not implemented',
                suggestContentTypeMatch: '=> Consider declaring how ' +
                    '"{{ content_type }}" content type ' + '\n' +
                    'should match with one of the following type of data: ' + '\n' +
                    '{"json", "text", "jsonp", "script", "html". "xml"}.' + '\n' +
                    'See also the description of "dataType" ajax setting ' + '\n' +
                    'for jQuery in ' +
                    'http://api.jquery.com/jquery.ajax/#jQuery-ajax-settings',
                suggestDataColumnNameCheck:
                    '=> The "data-column-name" attribute ' +
                    'can be defined for one of the target listener children.',
                suggestDependencyCheck: '=> The event listener "after" '
                    + 'property can be defined ' +
                    'to ensure requirements have been met ' +
                    'before attaching this event listener.',
                suggestListenersCheck: '=>  The event listener ' +
                    '"listeners" property can be defined.',
                suggestEmitterCheck: '=>  The "{{ emitter }}" emitter ' +
                    'should send a request to obtain a response with content.',
                suggestSelectorCheck: '=>  The "{{ selector }}" selector ' +
                    'should match existing elements.',
                invalidSenderId: 'Missing identity attribute on request sender '
                    + 'for "{{ event_type }}" event of ' +
                    '"{{ listener }}" listener:' + '\n' +
                    '=> The target listener "data-id" attribute can be defined.',
                invalidLoggingLevel: 'An invalid logging level has been set ' +
                    '("{{ logging_level }}").',
                invalidRequest: 'Invalid request for ' +
                    '"{{ event_type }}" event of "{{ listener }}" listener' + '\n'
                    + 'with message "{{ error }}."' + '\n'
                    + '=> The expected response code is "2xx".',
                invalidRequestUrl: 'Invalid request URL for ' +
                    '"{{ event_type }}" event of "{{ listener }}" listener:' + '\n'
                    + '=>  The event listener "request.url" property can be defined.'
                    + '\n' +
                    '=> The target listener "data-url" attribute can be defined.',
                uncompleteRequest: 'Uncomplete request required to update ' +
                    '"{{ entity }}" target with id #{{ id }}.',
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
                    + 'for "{{ listener }}" event listener.',
                updatedTargetEntityColumn: 'Updated "{{ column }}" column of ' +
                    '"{{ entity }}" target with id #{{ id }}.'
            };
            this.actions = {
                pollJob: 'poll-job'
            };
            this.events = {
                polledJob: 'job:polled'
            };
            this.promises = {};
            this.polling = {};

            this.fileSaver;
            this.remote = 'http://localhost';
            this.logger = logger;

            if (eventListeners !== undefined) {
                this.shouldBindEventListeners(eventListeners);
            }
        };

        jobsBoard.prototype.shouldBindEventListeners = function (eventListeners) {
            if (!Array.isArray(eventListeners)) {
                throw 'Event listeners should be passed as an array ' +
                    '(instance of "{{ type }}" given)'.replace(
                        '{{ type }}',
                        eventListeners.constructor.toString()
                    );
            }

            this.eventListenersByName = {};
            this.onPostEventHandlers = {};
            this.dependencies = {};
            this.dependees = {};
            this.eventListeners = eventListeners;
            this.checkDependenciesBetweenListeners();
        };

        jobsBoard.prototype.checkDependenciesBetweenListeners = function () {
            var self = this;
            var $ = this.$;
            $.each(self.eventListeners, function (index, eventListener) {
                if (eventListener.after !== undefined) {
                    var dependencies = self.dependencies[eventListener.name];
                    if (self.isUndefined(dependencies)) {
                        self.dependencies[eventListener.name] = [];
                    }

                    var dependees = self.dependees[eventListener.after];
                    if (self.isUndefined(dependees)) {
                        self.dependees[eventListener.after] = [];
                    }

                    self.dependencies[eventListener.name]
                        .push(eventListener.after);
                    self.dependees[eventListener.after]
                        .push(eventListener.name);
                }

                self.eventListenersByName[eventListener.name] = eventListener;
            });
        };

        jobsBoard.prototype.setRemote = function (remote) {
            this.remote = remote;
        };

        jobsBoard.prototype.setFileSaver = function (fileSaver) {
            this.fileSaver = fileSaver;
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
            var requestSender = request.getSender();
            var requestParent = requestSender.parents('[data-entity]');
            var promiseId;

            if (
                requestParent.length === 0 &&
                !this.isUndefined(requestSender.attr('data-entity'))
            ) {
                requestParent = requestSender;
            }

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
                type: request.getMethod(),
                dataType: request.getContentType(),
                processData: request.shouldProcessData(),
                xhrFields: {
                    responseType: request.getXhrResponseType()
                }
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

        jobsBoard.prototype.wrapCustomEventData = function (data, request) {
            if (data === undefined) {
                data = {
                    emitter: request.binding.name
                };
            } else {
                if (data.constructor && data.constructor === Object) {
                    data.emitter = request.binding.name;
                } else {
                    data = {
                        content: data,
                        emitter: request.binding.name
                    };
                }
            }
            data.promise = this.remindPromise(request);
            data.sender = request.getSender();

            return data;
        };

        jobsBoard.prototype.onOkResponse = function (request) {
            var promise = this.remindPromise(request);
            var self = this;

            if (this.shouldEmitEvent(request)) {
                promise.done(function (data) {
                    var eventType = request.success.emit;
                    var target = self.getOnSuccessTarget(request);
                    var event = $.Event(eventType);
                    target.trigger(event, self.wrapCustomEventData(data, request));
                });
            }
        };

        jobsBoard.prototype.isFunction = function (subject) {
            return subject && (typeof subject == 'function');
        };

        jobsBoard.prototype.isUndefined = function (subject) {
            return typeof subject == 'undefined';
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

        jobsBoard.prototype.appendRow = function (target, source, attributes) {
            var row = this.makeRow(source, attributes);

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

        jobsBoard.prototype.makeRow = function (columns, attributes) {
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

            if (attributes !== undefined) {
                $.each(attributes, function (name, value) {
                    row.attr(name, value);
                });
            }

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

        jobsBoard.prototype.getResponseHeader = function (promise, header) {
            var responseHeader;
            if (this.isFunction(promise.getResponseHeader)) {
                responseHeader = promise.getResponseHeader(header);
            }

            return responseHeader;
        };

        jobsBoard.prototype.getResponseFileName = function (promise) {
            var fileName = 'download.zip';
            var contentDisposition = this.getResponseHeader(
                promise,
                'Content-Disposition'
            );
            if (contentDisposition) {
                var fileNameMatches = contentDisposition
                    .match(/filename="([^"]+)"/);
                if (fileNameMatches[1] !== undefined) {
                    fileName = fileNameMatches[1];
                }
            }

            return fileName;
        };

        jobsBoard.prototype.getResponseContentType = function (promise) {
            var contentType = this.getResponseHeader(promise, 'Content-Type');
            if (!contentType) {
                contentType = 'text/plain';
            }

            return contentType;
        };

        jobsBoard.prototype.saveAs = function (data) {
            this.fileSaver(
                data.content,
                this.getResponseFileName(data.promise)
            );
        };

        jobsBoard.prototype.validateContent = function (data) {
            if (
                data === undefined ||
                data.content === undefined ||
                data.promise === undefined
            ) {
                var suggestEmitterCheck;

                if (data && data.emitter !== undefined)
                    suggestEmitterCheck = this.exceptions.suggestEmitterCheck
                    .replace('{{ emitter }}', data.emitter);
                else {
                    suggestEmitterCheck = '';
                }

                throw new Error(this.exceptions.noContentAvailable + '\n' +
                    suggestEmitterCheck);
            }
        };

        jobsBoard.prototype.onJobArchiveDownloaded = function (event) {
            var content = event.data;

            this.validateContent(content);
            this.saveAs(content);
        };

        jobsBoard.prototype.onJobsCreated = function (event) {
            var self = this;
            var $ = self.$;

            $.each(event.target, function (targetIndex, target) {
                self.appendRow(target, event.data.job, {
                    'data-action': self.actions.pollJob,
                    'data-listen-event': self.events.polledJob
                });
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

        jobsBoard.prototype.getDataColumnNameCheckSuggestion = function (target)
        {
            var suggestDataColumnNameCheck = '';
            if (target.attr('data-column-name') === undefined) {
                suggestDataColumnNameCheck = this.exceptions
                    .suggestDataColumnNameCheck;
            }

            return suggestDataColumnNameCheck;
        };

        jobsBoard.prototype.updateTargetColumns = function (
            target,
            columns,
            targetUpdatedOn
        ) {
            var updatedTarget = false;

            var self = this;
            $.each(columns, function (name, value) {
                var columnName = self.formatColumnName(name);
                var column = target.find('[data-column-name="{{ column }}"]'
                    .replace('{{ column }}', columnName));
                if (column.length === 0) {
                    self.logger.warn(self.exceptions.noColumnAvailable
                        .replace('{{ column }}', columnName)
                        .replace('{{ entity }}', target.attr('data-entity'))
                        .replace('{{ id }}', target.attr('data-id')) +
                        '\n' + self.getDataColumnNameCheckSuggestion(target)
                    );
                }

                var formattedValue = self.formatColumnValue(value, name);
                if (formattedValue[0].outerHTML !== column.html()) {
                    column.html(formattedValue);
                    self.logger.info(self.info.updatedTargetEntityColumn
                        .replace('{{ column }}', columnName)
                        .replace('{{ entity }}', target.attr('data-entity'))
                        .replace('{{ id }}', target.attr('data-id')));

                    if (columnName == targetUpdatedOn) {
                        updatedTarget = true;
                    }
                }
            });

            return updatedTarget;
        };

        jobsBoard.prototype.bindTargetChildrenListeners = function (target) {
            var self = this;
            var childrenListeners = target.find('[data-action]');
            if (childrenListeners.length > 0) {
                $.each(childrenListeners, function (index, listener) {
                    var listenerElement = $(listener);
                    var listenerName = listenerElement.attr('data-action');
                    var eventListener = self.eventListenersByName[listenerName];
                    if (eventListener) {
                        self.bindEvent(eventListener);
                    }
                });
            }
        };

        jobsBoard.prototype.onJobPolled = function (event) {
            var self = this;
            var target = event.target;
            var eventType = event.type;
            var data = event.data;
            var sender = event.data.sender;
            var jobId = event.data.id;

            if (data.entity === undefined) {
                throw new Error(this.exceptions.noResponseEntity
                    .replace('{{ emitter }}', data.emitter));
            }
            if (target.attr('data-entity') === undefined) {
                throw new Error(this.exceptions.noTargetEntity
                    .replace('{{ emitter }}', data.emitter));
            }
            if (target.attr('data-entity') !== data['entity']) {
                throw new Error(this.exceptions.mismatchingEntity
                    .replace('{{ entity }}', data['entity'])
                    .replace('{{ target_entity }}', target.attr('data-entity'))
                    .replace('{{ emitter }}', data.emitter)
                );
            }
            if (data.promise.readyState !== XMLHttpRequest.DONE) {
                self.logger.info(this.exceptions.uncompleteRequest
                    .replace('{{ entity }}', data['entity'])
                    .replace('{{ id }}', sender.attr('data-id')));

                return;
            }

            if (data.columns !== undefined) {
                $.each(target, function (elementIndex, element) {
                    var expectedEntityId = parseInt(sender.attr('data-id'), 10);
                    var elementId = parseInt($(element).attr('data-id'), 10);
                    var affectedRow = false;
                    var targetChild = $(element);
                    var pollingId;

                    if (jobId !== elementId) {
                        return;
                    }

                    (function (targetChild, columns) {
                        var updatedTarget = false;
                        var targetChildId = parseInt(targetChild.attr('data-id'));

                        if (targetChildId === expectedEntityId) {
                            updatedTarget = self.updateTargetColumns(
                                targetChild,
                                columns,
                                // Mark target as updated when this column is
                                'Output'
                            );
                            if (updatedTarget) {
                                affectedRow = true;
                            }
                        }
                    })(targetChild, data.columns);

                    pollingId = self.getPollingId(targetChild, data.emitter);
                    if (
                        data.promise.status === 200 &&
                        affectedRow &&
                        self.polling[pollingId] !== undefined
                    ) {
                        clearInterval(self.polling[pollingId]);
                        delete self.polling[pollingId];
                        self.bindTargetChildrenListeners(targetChild);

                        targetChild.off(eventType);

                        targetChild.removeAttr('data-action');
                        targetChild.removeAttr('data-listen-event');
                    }
                });
            }
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
                request.getSender().attr('data-url') === undefined
            ) {
                throw this.exceptions.invalidRequestUrl
                    .replace('{{ event_type }}', listener.eventType)
                    .replace('{{ listener }}', listener.name);
            }
        };

        jobsBoard.prototype.ensureRequestHasUrl = function (request) {
            var getter;
            var self = this;

            this.ensureRequestSenderHasUrl(request);

            if (
              request.url === undefined &&
              request.getSender().attr('data-url') !== undefined
            ) {
                getter = function () {
                    return request.getSender().attr('data-url');
                };
            } else if (typeof request.url === 'function') {
                getter = function () {
                    if (
                        !self.isFunction(request.getSender) ||
                        !self.isFunction(request.getSender().attr) ||
                        request.getSender().attr('data-id') === undefined
                    ) {
                        throw new Error(self.exceptions.invalidSenderId
                            .replace('{{ event_type }}', request.binding.type)
                            .replace('{{ listener }}', request.binding.name));
                    }
                    return request.url(request.getSender().attr('data-id'));
                };
            } else {
                getter = function () {
                    return request.url;
                };
            }

            request.getUrl = getter;
        };

        jobsBoard.prototype.updateRequestContentType = function (request) {
            var self = this;

            if (request.contentType !== undefined) {
                request.getContentType = function () {
                    var contentType;

                    if (request.contentType === 'application/zip') {
                        contentType = 'native';
                    } else {
                        var suggestContentTypeMatch;
                        if (request.contentType !== undefined) {
                            suggestContentTypeMatch = '\n' +
                                self.exceptions.suggestContentTypeMatch
                                .replace('{{ content_type }}', request.contentType);
                        } else {
                            suggestContentTypeMatch = '';
                        }

                        // Consider the possibility of
                        // handling other content types in the future
                        throw new Error(self.exceptions.notImplemented +
                            suggestContentTypeMatch);
                    }

                    return contentType;
                };

                request.getXhrResponseType = function () {
                    return 'blob';
                };

                request.shouldProcessData = function () {
                    return false;
                };
            } else {
                request.getContentType = function () {
                    return '*';
                };

                request.getXhrResponseType = function () {
                    return 'text';
                };

                request.shouldProcessData = function () {
                    return true;
                };
            }
        };

        jobsBoard.prototype.getXhrEmitter = function (binding) {
            var xhrEmitterFactory = this.getXhrEmitterFactory(binding);

            return xhrEmitterFactory();
        };

        jobsBoard.prototype.ensureRequestHasSender = function (request, sender) {
            var $ = this.$;

            request.getSender = function () {
                return $(sender);
            };
        };

        jobsBoard.prototype.getXhrEmitterFactory = function (binding) {
            var self = this;

            return function () {
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
                    self.ensureRequestHasSender(request, this);
                    self.ensureRequestHasUrl(request);
                    self.ensureRequestHasMethod(request);

                    self.updateRequestHeaders(request);
                    self.updateRequestContentType(request);

                    request = self.validateRequest(request);

                    var promise = self.makeXhrPromise(request);
                    promise.fail(function (xhr, status, error) {
                        throw new Error(self.exceptions.invalidRequest
                            .replace('{{ error }}', error)
                            .replace('{{ listener }}', request.binding.name)
                            .replace('{{ event_type }}', request.binding.type));
                    });
                    self.onOkResponse(request);

                    return promise;
                };
            };
        };

        jobsBoard.prototype.bindNativeEventHandlers = function (binding) {
            var self = this;
            var onEvent = self.getXhrEmitterFactory(binding);

            $.each(binding.listeners, function (listenerIndex, listener) {
                var eventListener = $(listener);
                var eventSubscriber = self.getEventSubscriber(
                    eventListener,
                    binding.type
                );

                // Re-bind the event subscriber to the listener
                eventSubscriber = eventSubscriber.bind(eventListener);

                // Detaches previously attached handler from the listener
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
                    var dependees = self.getBindingDependees(binding);
                    $.each(dependees, function (dependeeIndex, dependee) {
                        self.bindEvent(self.eventListenersByName[dependee]);
                    });
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
            var dependees = this.getBindingDependees(binding);

            return !this.isUndefined(dependees) && dependees.length > 0;
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
            }
        };

        jobsBoard.prototype.bindCustomEventHandlers = function (binding) {
            var self = this;
            self.updateBindingListeners(binding);

            binding.listeners.on(binding.type, function (event, data) {
                event.stopPropagation();

                var customEvent = {
                    data: data,
                    target: binding.listeners,
                    type: binding.type
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

            if (binding.type === 'polling') {
                if (binding.interval === undefined) {
                    binding.interval = this.DEFAULT_POLLING_INTERVAL_DELAY;
                }

                $.each(binding.listeners, function (listenerIndex, listener) {
                    var pollingId = self.getPollingId(
                        listener,
                        binding.name
                    );

                    self.polling[pollingId] = setInterval(
                        (function (binding) {
                            var eventListener = binding;

                            return function () {
                                var xhrEmitter = self.getXhrEmitter(eventListener);

                                var emitter = xhrEmitter.bind(listener);
                                emitter();
                            };
                        })(binding),
                        binding.interval
                    );
                });
            }
        };

        jobsBoard.prototype.getPollingId = function (listener, emitter) {
            var listenerElement = $(listener);
            var pollingIdParts = [
                emitter,
                listenerElement.attr('data-entity'),
                listenerElement.attr('data-id')
            ];

            return pollingIdParts.join('-');
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

        jobsBoard.prototype.getDependencyCheckSuggestion = function (binding) {
            var suggestDependencyCheck = '';
            if (this.isUndefined(binding.after)) {
                suggestDependencyCheck = this.exceptions
                    .suggestDependencyCheck + '\n';
            }

            return suggestDependencyCheck;
        };

        jobsBoard.prototype.getListenersCheckSuggestion = function (binding) {
            var suggestListenersCheck = '';
            if (this.isUndefined(binding.listeners)) {
                suggestListenersCheck = this.exceptions
                    .suggestListenersCheck + '\n';
            }

            return suggestListenersCheck;
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
                        this.getListenersCheckSuggestion(binding) +
                        this.getDependencyCheckSuggestion(binding) +
                        this.exceptions.suggestSelectorCheck
                            .replace(
                                '{{ selector }}',
                                binding.listeners.selector
                            )
                    );
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
                    .replace('{{ listener }}', binding.name) +
                    self.getDependencyCheckSuggestion(binding) +
                    checkSelector);
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
                if (
                    this.isUndefined(this.dependencies[listener.name]) ||
                    this.dependencies[listener.name].length === 0
                ) {
                    self.bindEvent(listener);
                }
            }
        };

        return new jobsBoard($, eventListeners);
    };
})(this, this);

(function (require) {
    if (!require.jQuery || !require.Routing || !require.saveAs) {
        return;
    }

    var getJobsBoard = require.getJobsBoard;
    var logger = require.logger;

    var container = $('[data-container="jobs-board"]');
    var routing = this.Routing;
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

    var fileSaver = this.saveAs;

    var listJobsUrl = schemeHost +
        routing.generate('weaving_the_web_api_get_jobs');
    var listedJobEventType = 'jobs:listed';

    var downloadedJobArchiveEvent = 'job:archive:downloaded';
    var downloadJobArchiveButtons = $('[data-action="download-job-archive"]');
    var saveJobArchiveButton = $('[data-action="save-job-archive"]');

    var jobsBoard = getJobsBoard($);

    var getPollJobUrl = function (jobId) {
        return schemeHost +
            routing.generate('weaving_the_web_api_get_job_output', {
                job: jobId
            });
    };
    var pollJobAction = 'poll-job';
    var jobRows = $('[data-action="' + pollJobAction + '"]');
    var polledJobSelector = $('[data-listen-event="{{ event_type }}"]'
        .replace('{{ event_type }}', jobsBoard.events.polledJob));

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
            contentType: 'application/zip',
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
    }, {
        after: 'post-job-creation',
        listeners: jobRows,
        name: 'poll-job',
        request: {
            url: getPollJobUrl,
            headers: headers,
            success: {
                emit: jobsBoard.events.polledJob
            }
        },
        type: 'polling'
    }, {
        after: 'post-job-creation',
        listeners: $(polledJobSelector),
        name: 'post-poll-job',
        type: jobsBoard.events.polledJob
    }];

    logger.enableLogging();
    logger.setLoggingLevel(logger.LOGGING_LEVEL.DEBUG);

    jobsBoard.shouldBindEventListeners(eventListeners);
    jobsBoard.setFileSaver(fileSaver);
    jobsBoard.setRemote(schemeHost);
    jobsBoard.mount();

    $('body').load();
})(this);
