<?php
declare(strict_types=1);

namespace App\Infrastructure\Repository\PublicationList;

use App\Api\Entity\Aggregate;

/**
 * @method Aggregate|null find($id, $lockMode = null, $lockVersion = null)
 * @method Aggregate|null findOneBy(array $criteria, array $orderBy = null)
 * @method Aggregate[]    findAll()
 * @method Aggregate[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
interface PublicationListRepositoryInterface
{
    /**
     * @param string $screenName
     * @param string $listName
     *
     * @return mixed
     */
    public function make(string $screenName, string $listName);
}