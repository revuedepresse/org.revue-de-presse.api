<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Api\Adapter;

use App\Twitter\Domain\Publication\StatusInterface;
use App\Twitter\Infrastructure\Operation\Collection\CollectionInterface;
use function hash;

class StatusToArray
{
    public static function fromStatusCollection(
        CollectionInterface $statusCollection
    ): CollectionInterface {
        return $statusCollection->map(
            function (StatusInterface $status) {
                return [
                    'legacy_id' => $status->getId(),
                    'hash' => hash('sha256', $status->getScreenName().'|'.$status->getStatusId()),
                    'avatar_url' => $status->getUserAvatar(),
                    'screen_name' => $status->getScreenName(),
                    'text' => $status->getText(),
                    'document_id' => $status->getStatusId(),
                    'document' => $status->getApiDocument(),
                    'published_at' => $status->getCreatedAt(),
                ];
            }
        );
    }
}