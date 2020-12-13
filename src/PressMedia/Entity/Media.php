<?php

namespace App\PressMedia\Entity;

use App\Membership\Domain\Entity\MemberInterface;
use Doctrine\Common\Collections\ArrayCollection;

class Media
{
    const TYPE_PHYSICAL_PERSON = 1;
    const TYPE_MORAL_PERSON = 2;
    const TYPE_MEDIA = 3;

    /**
     * @var string
     */
    private $id;

    /**
     * @var string
     */
    public $name;

    /**
     * @var integer
     */
    private $sourceId;

    /**
     * @var integer
     */
    private $type;

    /**
     * @var string
     */
    private $channel;

    /**
     * @var string
     */
    private $periodicity;

    /**
     * @var string
     */
    private $scope;

    /**
     * @var MemberInterface
     */
    private $member;

    /**
     * @var ArrayCollection
     */
    private $ownerships;

    public function __construct(
        int $sourceId,
        string $name,
        int $type,
        string $channel = null,
        string $periodicity = null,
        string $scope = null
    ) {
        $this->sourceId = $sourceId;
        $this->name = $name;
        $this->type = $type;
        $this->channel = $channel;
        $this->periodicity = $periodicity;
        $this->scope = $scope;
    }
}
