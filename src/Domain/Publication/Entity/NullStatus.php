<?php
declare(strict_types=1);

namespace App\Domain\Publication\Entity;

use App\Infrastructure\Api\Entity\Status;

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
