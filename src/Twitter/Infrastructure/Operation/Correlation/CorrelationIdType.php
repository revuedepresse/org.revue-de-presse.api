<?php
declare (strict_types=1);

namespace App\Twitter\Infrastructure\Operation\Correlation;

use InvalidArgumentException;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\GuidType;
use Doctrine\DBAL\Platforms\AbstractPlatform;

class CorrelationIdType extends GuidType
{
    const NAME = 'correlation_id';

    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof CorrelationIdInterface) {
            return $value;
        }

        try {
            $uuid = CorrelationId::fromString($value);
        } catch (InvalidArgumentException $e) {
            throw ConversionException::conversionFailed($value, static::NAME);
        }

        return $uuid;
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (
            $value instanceof CorrelationIdInterface
            || (is_string($value) || method_exists($value, '__toString'))
        ) {
            return (string) $value;
        }

        throw ConversionException::conversionFailed($value, static::NAME);
    }

    public function getName()
    {
        return static::NAME;
    }
}