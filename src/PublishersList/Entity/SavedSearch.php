<?php

namespace App\PublishersList\Entity;

class SavedSearch
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var \DateTime
     */
    private $createdAt;

    /**
     * @var string
     */
    private $searchId;

    /**
     * @var string
     */
    private $name;
    /**
     * @var string
     */
    public $searchQuery;

    /**
     * @param string    $searchQuery
     * @param string    $name
     * @param string    $searchId
     * @param \DateTime $createdAt
     */
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
