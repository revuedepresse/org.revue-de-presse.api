<?php
declare(strict_types=1);

namespace App\Search\Domain;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepositoryInterface;

interface SavedSearchAwareInterface
{
    public function setSavedSearchRepository(ServiceEntityRepositoryInterface $savedSearchRepository): SavedSearchAwareInterface;

    public function setSearchMatchingTweetRepository(ServiceEntityRepositoryInterface $searchMatchingTweetRepository): SavedSearchAwareInterface;
}
