<?php
declare(strict_types=1);

namespace App\Twitter\Domain\Api;

use App\Membership\Domain\Exception\ExceptionalMemberInterface;
use App\Membership\Domain\Exception\MembershipException;
use App\Twitter\Infrastructure\Http\Resource\MemberIdentity;

interface UnavailableResourceHandlerInterface extends ExceptionalMemberInterface
{
    /**
     * @param \App\Twitter\Infrastructure\Http\Resource\MemberIdentity $memberIdentity
     * @param UnavailableResourceInterface                             $resource
     *
     * @throws \App\Membership\Domain\Exception\MembershipException
     */
    public function handle(
        MemberIdentity $memberIdentity,
        UnavailableResourceInterface $resource
    ): void;
}