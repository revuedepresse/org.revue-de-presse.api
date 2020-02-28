<?php
declare(strict_types=1);

namespace App\Status;

interface LikedStatusCollectionAwareInterface
{
    public const INTENT_TO_FETCH_LIKES = 'intent_to_fetch_likes';

    /**
     * @param array $options
     * @return bool
     */
    public function isAboutToCollectLikesFromCriteria(array $options): bool;
}
