<?php

namespace App\Status\Entity;

use App\Member\Entity\MemberIdentity;
use WeavingTheWeb\Bundle\ApiBundle\Entity\Status;

class StatusIdentity
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var MemberIdentity
     */
    private $memberIdentity;

    /**
     * @var Status
     */
    private $status;

    /**
     * @var Status
     */
    private $archivedStatus;

    /**
     * @var string
     */
    private $twitterId;

    /**
     * @var \DateTime
     */
    private $publicationDateTime;
}
