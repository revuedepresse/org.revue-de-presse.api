<?php
declare(strict_types=1);

namespace App\Infrastructure\DependencyInjection\Publication;

use App\Infrastructure\Amqp\ResourceProcessor\PublishersListProcessorInterface;

trait PublishersListProcessorTrait
{
    private PublishersListProcessorInterface $publishersListProcessor;

    /**
     * @param PublishersListProcessorInterface $publishersListProcessor
     */
    public function setPublishersListProcessor(PublishersListProcessorInterface $publishersListProcessor)
    {
        $this->publishersListProcessor = $publishersListProcessor;
    }
}