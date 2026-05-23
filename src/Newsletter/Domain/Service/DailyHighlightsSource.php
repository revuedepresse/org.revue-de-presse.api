<?php
declare(strict_types=1);

namespace App\Newsletter\Domain\Service;

interface DailyHighlightsSource
{
    /**
     * @return HighlightView[]
     */
    public function fetchTop10(\DateTimeImmutable $date): array;
}
