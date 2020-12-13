<?php
declare(strict_types=1);

namespace App\Infrastructure\Amqp\ResourceProcessor;

use App\Infrastructure\Api\Entity\TokenInterface;
use App\Domain\Curation\PublicationStrategyInterface;
use App\Domain\Resource\PublishersList;

interface PublishersListProcessorInterface
{
    public function processPublishersList(
        PublishersList $list,
        TokenInterface $token,
        PublicationStrategyInterface $strategy
    ): int;
}