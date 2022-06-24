<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\DependencyInjection\Curation\Curator;

use App\Twitter\Domain\Curation\Curator\InterruptibleCuratorInterface;

trait InterruptibleCuratorTrait
{
    private InterruptibleCuratorInterface $interruptibleCurator;

    public function setInterruptibleCurator(
        InterruptibleCuratorInterface $interruptibleCurator
    ): self {
        $this->interruptibleCurator = $interruptibleCurator;

        return $this;
    }
}