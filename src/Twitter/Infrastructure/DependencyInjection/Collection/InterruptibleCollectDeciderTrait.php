<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\DependencyInjection\Collection;

use App\Twitter\Infrastructure\Twitter\Collector\InterruptibleCollectDeciderInterface;

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