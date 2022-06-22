<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Amqp\ResourceProcessor;

use App\Twitter\Domain\Api\Model\TokenInterface;
use App\Twitter\Domain\Curation\CurationStrategyInterface;
use App\Twitter\Domain\Resource\PublishersList;

interface PublishersListProcessorInterface
{
    public function processPublishersList(
        PublishersList $list,
        TokenInterface $token,
        CurationStrategyInterface $strategy
    ): int;
}