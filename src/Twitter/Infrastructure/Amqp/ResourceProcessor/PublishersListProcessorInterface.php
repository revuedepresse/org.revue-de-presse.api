<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Amqp\ResourceProcessor;

use App\Twitter\Infrastructure\Api\Entity\TokenInterface;
use App\Twitter\Domain\Curation\PublicationStrategyInterface;
use App\Twitter\Domain\Resource\PublishersList;

interface PublishersListProcessorInterface
{
    public function processPublishersList(
        PublishersList $list,
        TokenInterface $token,
        PublicationStrategyInterface $strategy
    ): int;
}