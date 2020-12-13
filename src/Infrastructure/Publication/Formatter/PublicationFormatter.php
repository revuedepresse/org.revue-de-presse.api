<?php
declare(strict_types=1);

namespace App\Infrastructure\Publication\Formatter;

use App\Conversation\ConversationAwareTrait;
use App\Infrastructure\Operation\Collection\Collection;
use App\Domain\Publication\Repository\PublicationInterface;

class PublicationFormatter implements PublicationFormatterInterface
{
    use ConversationAwareTrait;

    public function format(Collection $publications): Collection
    {
        return $publications->map(
            function (PublicationInterface $publication) {
                $extractedProperties                       = $this->extractStatusProperties(
                    [$publication]
                )[0];
                $extractedProperties['original_document'] = $publication->getDocument();

                return $extractedProperties;
            }
        );
    }
}