<?php
declare(strict_types=1);

namespace App\Api\Adapter;

use App\Api\Entity\StatusInterface;
use App\Operation\Collection\CollectionInterface;
use function array_map;
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