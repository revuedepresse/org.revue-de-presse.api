<?php
declare(strict_types=1);

namespace App\Infrastructure\DependencyInjection\Publication;

use App\Infrastructure\Amqp\ResourceProcessor\PublicationListProcessorInterface;

trait PublicationListProcessorTrait
{
    private PublicationListProcessorInterface $publicationListProcessor;

    /**
     * @param PublicationListProcessorInterface $publicationListProcessor
     */
    public function setPublicationListProcessor(PublicationListProcessorInterface $publicationListProcessor)
    {
        $this->publicationListProcessor = $publicationListProcessor;
    }
}