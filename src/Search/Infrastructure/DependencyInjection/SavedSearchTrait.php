<?php

namespace App\Search\Infrastructure\DependencyInjection;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepositoryInterface;
use App\Search\Domain\SavedSearchAwareInterface;

trait SavedSearchTrait
{
    private ServiceEntityRepositoryInterface $savedSearchRepository;
    private ServiceEntityRepositoryInterface $searchMatchingTweetRepository;

    public function setSavedSearchRepository(ServiceEntityRepositoryInterface $savedSearchRepository): SavedSearchAwareInterface {
        $this->savedSearchRepository = $savedSearchRepository;

        return $this;
    }

    public function setSearchMatchingTweetRepository(ServiceEntityRepositoryInterface $searchMatchingTweetRepository): SavedSearchAwareInterface {
        $this->searchMatchingTweetRepository = $searchMatchingTweetRepository;

        return $this;
    }
}
