<?php
declare(strict_types=1);

namespace App\NewsReview\Infrastructure\RealTimeDatabase\Firebase\Exception;

use Psr\Log\LoggerInterface;
use RuntimeException;

class UnvailableServiceAccountConfigurationFileException extends RuntimeException
{
    public static function guardAgainstMissingServiceAccountConfigurationFile(
        string $expectedServiceAccountConfigurationFileLocationInFileSystem,
        LoggerInterface $logger
    ) {
        if (
            file_exists($expectedServiceAccountConfigurationFileLocationInFileSystem) &&
            is_readable($expectedServiceAccountConfigurationFileLocationInFileSystem)
        ) {
            return;
        }

        $logger->critical(
            sprintf(
                implode([
                    'Missing service account configuration: ',
                    'please add the expected file to "%s"'
                ]),
                $expectedServiceAccountConfigurationFileLocationInFileSystem
            )
        );

        throw new self(
            sprintf(
                implode([
                    'Could not find or read service account configuration file ',
                    ', which is expected to be located at "%s"'
                ]),
                $expectedServiceAccountConfigurationFileLocationInFileSystem
            )
        );
    }
}