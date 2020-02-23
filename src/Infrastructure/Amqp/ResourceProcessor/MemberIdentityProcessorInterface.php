<?php
declare(strict_types=1);

namespace App\Infrastructure\Amqp\ResourceProcessor;

use App\Api\Entity\TokenInterface;
use App\Domain\Collection\PublicationStrategyInterface;
use App\Domain\Membership\Exception\ExceptionalMemberInterface;
use App\Domain\Resource\MemberIdentity;
use App\Domain\Resource\PublicationList;

interface MemberIdentityProcessorInterface extends ExceptionalMemberInterface
{
    /**
     * @param MemberIdentity               $memberIdentity
     * @param PublicationStrategyInterface $strategy
     * @param TokenInterface               $token
     * @param PublicationList              $list
     *
     * @return int
     */
    public function process(
        MemberIdentity $memberIdentity,
        PublicationStrategyInterface $strategy,
        TokenInterface $token,
        PublicationList $list
    ): void;
}