<?php
declare(strict_types=1);

namespace App\Twitter\Domain\Membership;

use App\Twitter\Domain\Membership\Exception\ExceptionalMemberInterface;
use Exception;

interface MemberFacingStrategyInterface extends ExceptionalMemberInterface
{
    public static function shouldBreakPublication(Exception $exception): bool;

    public static function shouldContinuePublication(Exception $exception): bool;
}