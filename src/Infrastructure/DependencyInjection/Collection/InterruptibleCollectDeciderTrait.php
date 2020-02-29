<?php
declare(strict_types=1);

namespace App\Infrastructure\DependencyInjection\Collection;

use App\Infrastructure\Twitter\Collector\InterruptibleCollectDeciderInterface;

trait InterruptibleCollectDeciderTrait
{
    private InterruptibleCollectDeciderInterface $interruptibleCollectDecider;

    public function setInterruptibleCollectDeciderInterface(
        InterruptibleCollectDeciderInterface $interruptibleCollectDecider
    ): self {
        $this->interruptibleCollectDecider = $interruptibleCollectDecider;

        return $this;
    }
}