<?php


namespace App\Infrastructure\DependencyInjection;

use App\Api\Repository\WhispererRepository;
use App\Domain\Membership\WhispererRepositoryInterface;

trait WhispererRepositoryTrait
{
    /**
     * @var WhispererRepositoryInterface $whispererRepository
     */
    protected WhispererRepositoryInterface $whispererRepository;

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