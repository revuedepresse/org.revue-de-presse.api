<?php

namespace App\Search\Domain\Entity;

class SavedSearch
{
    public const SEARCH_QUERY = 'search_query';

    private $id;

    private \DateTimeInterface $createdAt;

    private string $searchId;

    private string $name;

    public string $searchQuery;

    public function searchQuery(): string
    {
        return $this->searchQuery;
    }

    public function __construct(
        string $searchQuery,
        string $name,
        string $searchId,
        \DateTime $createdAt
    ) {
        $this->searchQuery = $searchQuery;
        $this->createdAt = $createdAt;
        $this->name = $name;
        $this->searchId = $searchId;
    }
}
