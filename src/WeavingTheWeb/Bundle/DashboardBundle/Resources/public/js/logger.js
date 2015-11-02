function getLogger(engine) {
    var Logger = function (engine) {
        this.console = engine;
    };

    Logger.prototype.info = function (message) {
        this.console.info(message);
    };

    Logger.prototype.error = function (message) {
        this.console.error(message);
    };

    return new Logger(engine);
}

if (window && window.console) {
    window.logger = getLogger(window.console);
}
