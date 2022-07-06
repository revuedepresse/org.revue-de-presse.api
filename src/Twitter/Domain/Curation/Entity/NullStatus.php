<?php
declare(strict_types=1);

namespace App\Twitter\Domain\Curation\Entity;

use App\Twitter\Infrastructure\Http\Entity\Tweet;

class NullStatus extends Tweet
{
    /**
     * @return int
     */
    public function getId(): int
    {
        return -1;
    }
}
