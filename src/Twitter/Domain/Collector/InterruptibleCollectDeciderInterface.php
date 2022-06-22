<?php
declare(strict_types=1);

namespace App\Twitter\Domain\Collector;

use App\Twitter\Domain\Curation\CurationSelectorsInterface;

interface InterruptibleCollectDeciderInterface
{
    public function decideWhetherCollectShouldBeSkipped(
        CurationSelectorsInterface $selectors,
        array                      $options
    );

    public function delayingConsumption(): bool;
}