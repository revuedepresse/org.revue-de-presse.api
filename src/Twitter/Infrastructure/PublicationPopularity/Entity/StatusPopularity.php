<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\PublicationPopularity\Entity;

use App\Twitter\Domain\Publication\TweetInterface;

class StatusPopularity
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var TweetInterface
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
