<?php
declare(strict_types=1);

namespace App\Twitter\Domain\Publication;

interface MembersListInterface
{
    public function totalStatus(): int;

    public function setTotalStatus(int $totalStatus): self;
}
