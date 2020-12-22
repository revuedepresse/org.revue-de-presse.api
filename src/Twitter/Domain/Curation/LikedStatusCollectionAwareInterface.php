<?php
declare(strict_types=1);

namespace App\Twitter\Domain\Curation;

interface LikedStatusCollectionAwareInterface
{
    public const INTENT_TO_FETCH_LIKES = 'fetch_likes';

    /**
     * @param array $options
     * @return bool
     */
    public function isAboutToCollectLikesFromCriteria(array $options): bool;
}
