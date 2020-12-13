<?php
declare(strict_types=1);

namespace App\Infrastructure\DependencyInjection\Formatter;

use App\Infrastructure\Publication\Formatter\PublicationFormatterInterface;

trait PublicationFormatterTrait
{
    private PublicationFormatterInterface $publicationFormatter;

    public function setPublicationFormatter(PublicationFormatterInterface $publicationFormatter): self
    {
        $this->publicationFormatter = $publicationFormatter;

        return $this;
    }
}