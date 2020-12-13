<?php
declare(strict_types=1);

namespace App\Infrastructure\DependencyInjection;

use App\Domain\Status\Repository\TimelyStatusRepositoryInterface;

trait TimelyStatusRepositoryTrait
{
    protected TimelyStatusRepositoryInterface $timelyStatusRepository;

    public function setTimelyStatusRepository(TimelyStatusRepositoryInterface $timelyStatusRepository): self
    {
        $this->timelyStatusRepository = $timelyStatusRepository;

        return $this;
    }
}