<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Twitter\Api;

use App\Twitter\Domain\Membership\Exception\ExceptionalMemberInterface;
use App\Twitter\Domain\Membership\Exception\MembershipException;
use App\Twitter\Domain\Resource\MemberIdentity;

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