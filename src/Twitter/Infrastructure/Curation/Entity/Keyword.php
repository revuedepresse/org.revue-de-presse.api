<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Curation\Entity;

use App\Membership\Domain\Model\MemberInterface;
use App\Twitter\Domain\Publication\PublishersListInterface;
use Predis\Configuration\Option\Aggregate;
use App\Twitter\Domain\Publication\StatusInterface;

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

    private PublishersListInterface $aggregate;

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
