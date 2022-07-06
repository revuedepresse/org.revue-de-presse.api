<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Amqp\ResourceProcessor;

use App\Twitter\Domain\Http\Model\TokenInterface;
use App\Twitter\Domain\Curation\CurationRulesetInterface;
use App\Twitter\Infrastructure\Http\Resource\PublishersList;

interface PublishersListProcessorInterface
{
    public function processPublishersList(
        PublishersList $list,
        TokenInterface $token,
        CurationRulesetInterface $ruleset
    ): int;
}