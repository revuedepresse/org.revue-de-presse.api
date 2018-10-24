<?php

namespace App\Aggregate\Entity;

class SavedSearch
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var string
     */
    private $query;

    /**
     * @param string $query
     */
    public function __construct(string $query)
    {
        $this->query = $query;
    }
}
