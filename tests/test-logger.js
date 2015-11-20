'use strict';

(function (require) {
    describe('Logger', function () {
        var getLogger = require.getLogger;
        var logger;
        var consoleSpy;

        beforeEach(function () {
            consoleSpy = jasmine.createSpyObj(
                'console',
                ['debug', 'info', 'error']
            );
            consoleSpy.debug = jasmine.createSpy().and.returnValue(null);
            consoleSpy.info = jasmine.createSpy().and.returnValue(null);
            consoleSpy.error = jasmine.createSpy().and.returnValue(null);
            logger = getLogger(consoleSpy);
        });

        it('should log messages only when logging has been enabled',
            function () {
                logger.info('info message');

                expect(consoleSpy.info).not.toHaveBeenCalled();

                logger.enableLogging();
                logger.info('info message');
                expect(consoleSpy.info).toHaveBeenCalled();
            }
        );

        it('should log messages according to the logging level', function () {
            logger.enableLogging();
            logger.setLoggingLevel(logger.LOGGING_LEVEL.ERROR);

            expect(logger.isLoggerActive('info')).toBeFalsy();
            expect(logger.shouldLog('info')).toBeFalsy();

            logger.info('info message');
            expect(consoleSpy.info).not.toHaveBeenCalled();

            expect(logger.isLoggerActive('error')).toBeTruthy();
            expect(logger.shouldLog('error')).toBeTruthy();

            logger.error('error message');
            expect(consoleSpy.error).toHaveBeenCalled();

            logger.setLoggingLevel(logger.LOGGING_LEVEL.DEBUG);
            expect(logger.isLoggerActive('info')).toBeTruthy();
            expect(logger.shouldLog('info')).toBeTruthy();

            logger.info('info message');
            expect(consoleSpy.info).toHaveBeenCalled();
        });

        it('should log messages according to the logging filter', function () {
            logger.enableLogging();
            logger.filterLogByLevel(logger.LOGGING_LEVEL.DEBUG);

            logger.info('info message');
            expect(consoleSpy.info).not.toHaveBeenCalled();

            expect(logger.logFilter).toEqual(logger.LOGGING_LEVEL.DEBUG);
            expect(logger.shouldLog('debug')).toBeFalsy();
            expect(logger.isLoggerActive('debug')).toBeFalsy();

            logger.debug('debug message');
            expect(consoleSpy.debug).not.toHaveBeenCalled();

            logger.setLoggingLevel(logger.LOGGING_LEVEL.DEBUG);
            expect(logger.shouldLog('debug')).toBeTruthy();
            expect(logger.isLoggerActive('debug')).toBeTruthy();

            logger.debug('debug message');
            expect(consoleSpy.debug).toHaveBeenCalled();
        });
    });
})(window);
