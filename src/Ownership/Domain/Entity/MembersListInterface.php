<?php
declare(strict_types=1);

namespace App\Ownership\Domain\Entity;

interface MembersListInterface
{
    public function totalStatus(): int;

    public function setTotalStatus(int $totalStatus): self;
}
