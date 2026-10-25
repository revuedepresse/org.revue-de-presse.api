<?php
declare(strict_types=1);

namespace App\Newsletter\Domain\Service;

use App\Newsletter\Domain\ValueObject\Edition;

interface DailyHighlightsSource
{
    /**
     * @return HighlightView[]
     */
    public function fetchTop10(\DateTimeImmutable $date, Edition $edition = Edition::FrenchMedia): array;
}
