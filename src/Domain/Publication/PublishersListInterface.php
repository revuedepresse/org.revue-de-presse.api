<?php
declare(strict_types=1);

namespace App\Domain\Publication;

interface PublishersListInterface
{
    public function totalStatus(): int;

    public function setTotalStatus(int $totalStatus): self;
}