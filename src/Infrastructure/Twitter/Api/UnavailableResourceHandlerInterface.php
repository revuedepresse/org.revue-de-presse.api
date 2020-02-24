<?php
declare(strict_types=1);

namespace App\Infrastructure\Twitter\Api;

use App\Domain\Membership\Exception\ExceptionalMemberInterface;
use App\Domain\Membership\Exception\MembershipException;
use App\Domain\Resource\MemberIdentity;

interface UnavailableResourceHandlerInterface extends ExceptionalMemberInterface
{
    /**
     * @param MemberIdentity                $memberIdentity
     * @param UnavailableResourceInterface $resource
     *
     * @throws MembershipException
     */
    public function handle(
        MemberIdentity $memberIdentity,
        UnavailableResourceInterface $resource
    ): void;
}