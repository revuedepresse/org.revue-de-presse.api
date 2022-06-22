<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Amqp\ResourceProcessor;

use App\Twitter\Domain\Api\Model\TokenInterface;
use App\Twitter\Domain\Curation\CurationStrategyInterface;
use App\Twitter\Domain\Resource\MemberIdentity;
use App\Twitter\Domain\Resource\PublishersList;

interface MemberIdentityProcessorInterface
{
    /**
     * @param MemberIdentity               $memberIdentity
     * @param CurationStrategyInterface $strategy
     * @param TokenInterface               $token
     * @param PublishersList              $list
     *
     * @return int
     */
    public function process(
        MemberIdentity            $memberIdentity,
        CurationStrategyInterface $strategy,
        TokenInterface            $token,
        PublishersList            $list
    ): int;
}