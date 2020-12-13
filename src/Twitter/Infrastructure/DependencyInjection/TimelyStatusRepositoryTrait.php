<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\DependencyInjection;

use App\Twitter\Domain\Publication\Repository\TimelyStatusRepositoryInterface;

trait TimelyStatusRepositoryTrait
{
    protected TimelyStatusRepositoryInterface $timelyStatusRepository;

    public function setTimelyStatusRepository(TimelyStatusRepositoryInterface $timelyStatusRepository): self
    {
        $this->timelyStatusRepository = $timelyStatusRepository;

        return $this;
    }
}