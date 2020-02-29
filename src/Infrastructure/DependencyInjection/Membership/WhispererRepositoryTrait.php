<?php
declare(strict_types=1);

namespace App\Infrastructure\DependencyInjection\Membership;

use App\Infrastructure\Repository\Membership\WhispererRepositoryInterface;

trait WhispererRepositoryTrait
{
    private WhispererRepositoryInterface $whispererRepository;

    public function setWhispererRepository(
        WhispererRepositoryInterface $whispererRepository
    ): self {
        $this->whispererRepository = $whispererRepository;

        return $this;
    }
}