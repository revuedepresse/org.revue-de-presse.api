<?php

namespace App\Search\Infrastructure\Repository;

use App\Search\Domain\Entity\SavedSearch;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Exception;

class SavedSearchRepository extends ServiceEntityRepository
{
    /**
     * @throws Exception
     */
    public function make(\stdClass $response): SavedSearch
    {
        return new SavedSearch(
            $response->query,
            $response->name,
            $response->id,
            new DateTime($response->created_at, new \DateTimeZone('UTC'))
        );
    }

    public function save(SavedSearch $savedSearch): SavedSearch
    {
        $this->getEntityManager()->persist($savedSearch);
        $this->getEntityManager()->flush();

        return $savedSearch;
    }
}
