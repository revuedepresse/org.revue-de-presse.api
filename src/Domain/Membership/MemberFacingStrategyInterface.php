<?php
declare(strict_types=1);

namespace App\Domain\Membership;

use App\Domain\Membership\Exception\ExceptionalMemberInterface;
use Exception;

interface MemberFacingStrategyInterface extends ExceptionalMemberInterface
{
    public static function shouldBreakPublication(Exception $exception): bool;

    public static function shouldContinuePublication(Exception $exception): bool;
}