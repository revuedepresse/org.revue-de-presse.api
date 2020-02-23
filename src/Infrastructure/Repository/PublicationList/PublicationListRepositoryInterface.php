<?php
declare(strict_types=1);

namespace App\Infrastructure\Repository\PublicationList;

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