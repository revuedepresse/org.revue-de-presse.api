'use strict';

(function (exports, require) {
    var nativeLogger = require.console;

    /**
     * Inject window.console when available
     *
     * @param {Object} engine
     * @returns {Logger}
     */
    exports.getLogger = function (engine) {

        /**
         * @param {Object} engine
         * @constructor
         */
        var Logger = function (engine) {
            this.errors = {
                invalidLoggingLevel: 'An invalid logging level has been set ' +
                    '("{{ logging_level }}").'
            };
            var LOGGING_SEVERITY_LEVEL = {
                DEBUG: 10,
                ERROR: 40,
                INFO: 20,
                NONE: 50,
                WARN: 30
            };
            this.engine = engine;
            this.logger = {};
            this.LOGGING_SEVERITY_LEVEL = LOGGING_SEVERITY_LEVEL;
            this.LOGGING_LEVEL = {};
            var loggingLevel;
            for (loggingLevel in this.LOGGING_SEVERITY_LEVEL) {
                this.LOGGING_LEVEL[loggingLevel] = loggingLevel;
            }
            this.loggingLevel = this.LOGGING_LEVEL.INFO;

            this.decorateLoggingEngine();
        };

        /**
         * Decide whether the logging engine should output messages
         *
         * @param {String} logLevel
         * @returns {boolean}
         */
        Logger.prototype.shouldLog = function (logLevel) {
            return this.isLoggerActive(logLevel) && (
                this.logFilter === undefined ||
                this.logFilter === logLevel.toUpperCase()
            );
        };

        /**
         * Decorate provided logging engine to filter messages
         * or to prevent them from being output entirely.
         */
        Logger.prototype.decorateLoggingEngine = function () {
            var self = this;

            if (this.engine) {
                var log;
                for (log in this.engine) {
                    if (typeof this.engine[log].constructor == 'function') {
                        (function (logLevel) {
                            self[logLevel] = function () {
                                var i;
                                var args = [];
                                for (i = 0; i < arguments.length; i++) {
                                    args.push(arguments[i]);
                                }

                                if (self.shouldLog(logLevel)) {
                                    this.engine[logLevel]
                                        .apply(this.engine, args);
                                }
                            };
                        })(log);
                    }
                }
            }
        };

        /**
         * Log a stack trace
         *
         * @param {Boolean} condition
         * @returns {*}
         */
        Logger.prototype.logStackUnderCondition = function (condition) {
            if (condition) {
                this.logger.debug((new Error).stack);
            }

            return condition;
        };

        /**
         * Decide if the logger is active or not according to the criticality level of the messages
         *
         * @param {String} logger
         * @returns {boolean}
         */
        Logger.prototype.isLoggerActive = function (logger) {
            var logLevelForLogger = logger.toUpperCase();
            if (this.activeLogging) {
                return this.getLoggingLevelSeverity(logLevelForLogger) >=
                    this.getLoggingLevelSeverity(this.loggingLevel);
            }
        };

        /**
         * Check if the logging level is known by the logger
         *
         * @param {String} level
         * @returns {*}
         */
        Logger.prototype.validateLoggingLevel = function (level) {
            if (level !== undefined) {
                if (this.LOGGING_LEVEL[level] !== undefined) {
                    return level;
                } else {

                    if (nativeLogger && nativeLogger.debug) {
                        // Prevent infinite looping
                        nativeLogger.debug(this.errors.invalidLoggingLevel
                            .replace('{{ logging_level }}', level));
                    }
                }
            }

            return this.LOGGING_LEVEL.NONE;
        };

        /**
         * Return a higher number when the severity increases
         *
         * @param {String} level
         * @returns {*}
         */
        Logger.prototype.getLoggingLevelSeverity = function (level) {
            return this.LOGGING_SEVERITY_LEVEL[
                this.validateLoggingLevel(level)
            ];
        };

        /**
         * @returns {Logger}
         */
        Logger.prototype.enableLogging = function () {
            this.activeLogging = true;

            return this;
        };

        /**
         * @param {String} level
         */
        Logger.prototype.filterLogByLevel = function (level) {
            this.logFilter = level;
        };

        /**
         * @param {String} level
         * @returns {Logger}
         */
        Logger.prototype.setLoggingLevel = function (level) {
            this.loggingLevel = this.validateLoggingLevel(level);

            return this;
        };

        /**
         * @returns {Logger}
         */
        Logger.prototype.disableLogging = function () {
            this.activeLogging = false;

            return this;
        };

        return new Logger(engine);
    };

    exports.logger = require.getLogger(require.console);
})(window, window);
