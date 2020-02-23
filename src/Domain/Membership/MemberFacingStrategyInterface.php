<?php
declare(strict_types=1);

namespace App\Domain\Membership;

use Exception;

interface MemberFacingStrategyInterface
{
    public const NOT_FOUND_MEMBER = 10;

    public const UNAVAILABLE_RESOURCE = 20;

    public const UNEXPECTED_ERROR = 40;

    public const SUSPENDED_USER = 50;

    public const PROTECTED_ACCOUNT = 60;

    public static function shouldBreakPublication(Exception $exception): bool;

    public static function shouldContinuePublication(Exception $exception): bool;
}