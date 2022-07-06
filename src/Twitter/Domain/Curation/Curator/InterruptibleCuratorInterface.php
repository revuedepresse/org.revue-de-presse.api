<?php
declare(strict_types=1);

namespace App\Twitter\Domain\Curation\Curator;

use App\Twitter\Domain\Curation\CurationSelectorsInterface;

interface InterruptibleCuratorInterface
{
    public function curateTweets(
        CurationSelectorsInterface $selectors,
        array                      $options
    );

    public function delayingConsumption(): bool;
}