<?php
declare(strict_types=1);

namespace App\Infrastructure\Amqp\ResourceProcessor;

use App\Infrastructure\Api\Entity\TokenInterface;
use App\Domain\Collection\PublicationStrategyInterface;
use App\Domain\Resource\PublicationList;

interface PublicationListProcessorInterface
{
    public function processPublicationList(
        PublicationList $list,
        TokenInterface $token,
        PublicationStrategyInterface $strategy
    ): int;
}