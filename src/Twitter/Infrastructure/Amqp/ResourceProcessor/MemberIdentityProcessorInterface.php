<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Amqp\ResourceProcessor;

use App\Twitter\Domain\Api\Model\TokenInterface;
use App\Twitter\Domain\Curation\CurationRulesetInterface;
use App\Twitter\Domain\Membership\Exception\MembershipException;
use App\Twitter\Domain\Resource\MemberIdentity;
use App\Twitter\Domain\Resource\PublishersList;
use App\Twitter\Infrastructure\Amqp\Exception\ContinuePublicationException;
use App\Twitter\Infrastructure\Amqp\Exception\StopPublicationException;

interface MemberIdentityProcessorInterface
{
    /**
     * @throws ContinuePublicationException
     * @throws MembershipException
     * @throws StopPublicationException
     */
    public function process(
        MemberIdentity           $memberIdentity,
        CurationRulesetInterface $ruleset,
        TokenInterface           $token,
        PublishersList           $list
    ): int;
}