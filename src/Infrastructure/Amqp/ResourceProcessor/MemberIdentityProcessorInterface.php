<?php
declare(strict_types=1);

namespace App\Infrastructure\Amqp\ResourceProcessor;

use App\Api\Entity\TokenInterface;
use App\Domain\Collection\PublicationStrategyInterface;
use App\Domain\Resource\MemberIdentity;
use App\Domain\Resource\PublicationList;

interface MemberIdentityProcessorInterface
{
    /**
     * @param MemberIdentity               $memberIdentity
     * @param PublicationStrategyInterface $strategy
     * @param TokenInterface               $token
     * @param PublicationList              $list
     *
     * @return void
     */
    public function process(
        MemberIdentity $memberIdentity,
        PublicationStrategyInterface $strategy,
        TokenInterface $token,
        PublicationList $list
    ): void;
}