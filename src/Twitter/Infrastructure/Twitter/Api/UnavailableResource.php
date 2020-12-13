<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Twitter\Api;

use App\Twitter\Infrastructure\Twitter\Api\Exception\InvalidTwitterErrorCodeException;
use function in_array;

class UnavailableResource implements UnavailableResourceInterface
{
    /**
     * @var int
     */
    private int $type;

    /**
     * @var string
     */
    private string $rootCause;

    private function __construct(int $type, string $rootCause)
    {
        if (!in_array($type, self::ERROR_CODES, true)) {
            InvalidTwitterErrorCodeException::throws('Invalid resource type.');
        }

        $this->type = $type;
        $this->rootCause = $rootCause;
    }

    public static function ofTypeAndRootCause(int $type, string $rootCause): self
    {
        return new self($type, $rootCause);
    }

    public function getType(): int
    {
        return $this->type;
    }

    public function getMessage(): string
    {
        return $this->rootCause;
    }

    public function isResourceNotFound(): bool
    {
        return $this->type === self::ERROR_NOT_FOUND;
    }

    public function isMemberNotFound(): bool
    {
        return $this->type === self::ERROR_USER_NOT_FOUND;
    }

    public function isMemberSuspended(): bool
    {
        return $this->type === self::ERROR_SUSPENDED_USER;
    }

    public function isMemberProtected(): bool
    {
        return $this->type === self::ERROR_PROTECTED_TWEET;
    }
}