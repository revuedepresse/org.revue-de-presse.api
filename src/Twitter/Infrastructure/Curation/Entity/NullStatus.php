<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Curation\Entity;

use App\Twitter\Infrastructure\Api\Entity\Status;

class NullStatus extends Status
{
    /**
     * @return int
     */
    public function getId(): int
    {
        return -1;
    }
}
