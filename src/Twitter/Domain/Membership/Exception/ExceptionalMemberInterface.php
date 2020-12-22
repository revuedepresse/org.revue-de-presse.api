<?php
declare(strict_types=1);

namespace App\Twitter\Domain\Membership\Exception;

interface ExceptionalMemberInterface
{
    public const NOT_FOUND_MEMBER = 10;

    public const UNAVAILABLE_RESOURCE = 20;

    public const UNEXPECTED_ERROR = 40;

    public const SUSPENDED_USER = 50;

    public const PROTECTED_ACCOUNT = 60;

}