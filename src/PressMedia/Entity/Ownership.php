<?php


namespace App\PressMedia\Entity;

class Ownership
{

    private $id;

    /**
     * @var Media
     */
    private $media;

    /**
     * @var Owner
     */
    private $owner;

    /**
     * @var float
     */
    private $shares;

    /**
     * @var string
     */
    private $ownershipLevel;

    /**
     * @param Owner $owner
     * @param Media $media
     * @param null  $shares
     * @param null  $ownershipLevel
     */
    public function __construct(
        Owner $owner,
        Media $media,
        $shares = null,
        $ownershipLevel = null
    ) {
        $this->owner = $owner;
        $this->media = $media;
        $this->shares = $shares;
        $this->ownershipLevel = $ownershipLevel;
    }
}
