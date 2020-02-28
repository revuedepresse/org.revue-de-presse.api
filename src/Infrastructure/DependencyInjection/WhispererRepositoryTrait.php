<?php
declare(strict_types=1);

namespace App\Infrastructure\DependencyInjection;

use App\Infrastructure\Repository\Membership\WhispererRepositoryInterface;

trait WhispererRepositoryTrait
{
    /**
     * @var WhispererRepositoryInterface $whispererRepository
     */
    private WhispererRepositoryInterface $whispererRepository;

    /**
     * @param $whispererRepository
     * @return $this
     */
    public function setWhispererRepository(WhispererRepositoryInterface $whispererRepository): self
    {
        $this->whispererRepository = $whispererRepository;

        return $this;
    }
}