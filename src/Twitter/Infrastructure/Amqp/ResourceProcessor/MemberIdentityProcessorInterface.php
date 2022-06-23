<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Amqp\ResourceProcessor;

use App\Membership\Domain\Exception\MembershipException;
use App\Twitter\Domain\Api\Model\TokenInterface;
use App\Twitter\Domain\Curation\CurationRulesetInterface;
use App\Twitter\Infrastructure\Amqp\Exception\ContinuePublicationException;
use App\Twitter\Infrastructure\Amqp\Exception\StopPublicationException;
use App\Twitter\Infrastructure\Http\Resource\MemberIdentity;
use App\Twitter\Infrastructure\Http\Resource\PublishersList;

interface MemberIdentityProcessorInterface
{
    /**
     * @throws ContinuePublicationException
     * @throws \App\Membership\Domain\Exception\MembershipException
     * @throws StopPublicationException
     */
    public function process(
        MemberIdentity           $memberIdentity,
        CurationRulesetInterface $ruleset,
        TokenInterface           $token,
        PublishersList           $list
    ): int;
}