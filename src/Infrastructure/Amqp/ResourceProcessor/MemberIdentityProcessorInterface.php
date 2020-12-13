<?php
declare(strict_types=1);

namespace App\Infrastructure\Amqp\ResourceProcessor;

use App\Infrastructure\Api\Entity\TokenInterface;
use App\Domain\Curation\PublicationStrategyInterface;
use App\Domain\Resource\MemberIdentity;
use App\Domain\Resource\PublishersList;

interface MemberIdentityProcessorInterface
{
    /**
     * @param MemberIdentity               $memberIdentity
     * @param PublicationStrategyInterface $strategy
     * @param TokenInterface               $token
     * @param PublishersList              $list
     *
     * @return int
     */
    public function process(
        MemberIdentity $memberIdentity,
        PublicationStrategyInterface $strategy,
        TokenInterface $token,
        PublishersList $list
    ): int;
}