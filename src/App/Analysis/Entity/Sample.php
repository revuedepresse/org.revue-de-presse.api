<?php

namespace App\Analysis\Entity;

use Doctrine\Common\Collections\ArrayCollection;

class Sample
{
    /**
     * @var $id
     */
    private $id;

    /**
     * @var string
     */
    private $label;

    /**
     * @var \DateTime
     */
    private $createdAt;

    /**
     * @var ArrayCollection
     */
    private $publicationFrequencies;

    public function __construct()
    {
        $this->publicationFrequencies = new ArrayCollection();
    }
}
