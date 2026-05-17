<?php
declare(strict_types=1);

namespace App\NewsReview\Domain\Snapshot\Filter;

final class HighlightFilters
{
    public function apply(array $rawStatuses, array $queryParams): array
    {
        $items = $rawStatuses;

        if (!empty($queryParams['distinctSources'])) {
            $items = $this->distinctBy($items, 'screen_name');
        }

        if (isset($queryParams['term']) && $queryParams['term'] !== '') {
            $needle = mb_strtolower((string) $queryParams['term']);
            $items = array_values(array_filter(
                $items,
                static fn(array $item): bool => isset($item['text'])
                    && str_contains(mb_strtolower((string) $item['text']), $needle),
            ));
        }

        $selected = $queryParams['selectedAggregates'] ?? [];
        if (is_array($selected) && $selected !== []) {
            $items = array_values(array_filter(
                $items,
                static fn(array $item): bool => isset($item['aggregate'])
                    && in_array($item['aggregate'], $selected, true),
            ));
        }

        return $items;
    }

    private function distinctBy(array $items, string $key): array
    {
        $seen = [];
        $output = [];
        foreach ($items as $item) {
            $value = $item[$key] ?? null;
            if ($value === null || isset($seen[$value])) {
                continue;
            }
            $seen[$value] = true;
            $output[] = $item;
        }

        return $output;
    }
}
