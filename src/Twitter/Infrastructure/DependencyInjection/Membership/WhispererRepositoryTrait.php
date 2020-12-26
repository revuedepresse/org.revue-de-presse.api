<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\DependencyInjection\Membership;

use App\Twitter\Domain\Membership\Repository\WhispererRepositoryInterface;

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