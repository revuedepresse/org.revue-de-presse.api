<?php
declare(strict_types=1);

namespace App\Membership\Domain\Compliance;

use App\Membership\Domain\Exception\ExceptionalMemberInterface;
use Exception;

interface ApiComplianceInterface extends ExceptionalMemberInterface
{
    public static function shouldBreakPublication(Exception $exception): bool;

    public static function shouldContinuePublication(Exception $exception): bool;
}