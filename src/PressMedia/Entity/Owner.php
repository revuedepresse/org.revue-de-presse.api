<?php

namespace App\PressMedia\Entity;

use App\PressMedia\Repository\OwnerRepository;
use Doctrine\Common\Collections\ArrayCollection;

class Owner
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var integer
     */
    private $sourceId;

    /**
     * @var string
     */
    public $name;

    /**
     * @var ArrayCollection
     */
    public $ownerships;

    /**
     * @param int         $sourceId
     * @param string      $name
     * @param float|null  $shares
     * @param string|null $ownershipLevel
     */
    public function __construct(
        int $sourceId,
        string $name
    ) {
        $this->sourceId = $sourceId;
        $this->name = $name;

        $this->ownerships  = new ArrayCollection();
    }

    /**
     * @param Media $media
     * @return Ownership
     */
    public function hasRelationshipWithRegardsToMedia(
        Media $media,
        float $shares = null,
        string $ownershipLevel = null
    ): Ownership {
        $ownership = new Ownership(
            $this,
            $media,
            $shares,
            $ownershipLevel
        );

        if ($this->ownerships->contains($ownership)) {
            return $ownership;
        }

        $this->ownerships->add($ownership);

        return $ownership;
    }
}
