<?php

namespace App\Member\Entity;

use App\Member\MemberInterface;
use App\Status\Entity\StatusIdentity;
use Doctrine\Common\Collections\ArrayCollection;

class MemberIdentity
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var MemberInterface
     */
    private $member;

    /**
     * @var string
     */
    private $screenName;

    /**
     * @var int
     */
    private $twitterId;

    /**
     * @var StatusIdentity[]
     */
    private $statusIdentities;

    public function __construct()
    {
        $this->statusIdentities = new ArrayCollection();
    }
}
