<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Amqp\ResourceProcessor;

use App\Twitter\Infrastructure\Api\Entity\TokenInterface;
use App\Twitter\Domain\Curation\PublicationStrategyInterface;
use App\Twitter\Domain\Resource\MemberIdentity;
use App\Twitter\Domain\Resource\PublishersList;

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