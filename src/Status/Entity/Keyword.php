<?php
declare(strict_types=1);

namespace App\Status\Entity;

use App\Membership\Entity\MemberInterface;
use Predis\Configuration\Option\Aggregate;
use App\Domain\Status\StatusInterface;

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
