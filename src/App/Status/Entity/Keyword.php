<?php

namespace App\Status\Entity;

use App\Member\MemberInterface;
use Predis\Configuration\Option\Aggregate;
use WeavingTheWeb\Bundle\ApiBundle\Entity\StatusInterface;

class Keyword
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var string
     */
    private $keyword;

    /**
     * @var StatusInterface
     */
    private $status;

    /**
     * @var MemberInterface
     */
    private $member;

    /**
     * @var Aggregate
     */
    private $aggregate;

    /**
     * @var string
     */
    private $aggregateName;

    /**
     * @var \DateTime
     */
    private $publicationDateTime;

    /**
     * @var integer
     */
    private $occurrences;
}
