<?php
declare(strict_types=1);

namespace App\PublicationPopularity\Entity;

use App\Twitter\Domain\Publication\StatusInterface;

class StatusPopularity
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var StatusInterface
     */
    private $status;

    /**
     * @var integer
     */
    private $totalRetweets;

    /**
     * @var integer
     */
    private $totalFavorites;

    /**
     * @var \DateTime
     */
    private $checkedAt;
}
