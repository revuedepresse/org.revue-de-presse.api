<?php
declare(strict_types=1);

namespace App\Newsletter\Domain\ValueObject;

final class InvalidEdition extends \DomainException
{
    public function __construct(string $value)
    {
        parent::__construct(sprintf('Unknown newsletter edition "%s"', $value));
    }
}
