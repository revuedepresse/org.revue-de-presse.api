<?php
declare(strict_types=1);

namespace App\Tests\NewsReview\Domain;

use App\NewsReview\Domain\Snapshot\Filter\HighlightFilters;
use PHPUnit\Framework\TestCase;

class HighlightFiltersTest extends TestCase
{
    private HighlightFilters $filters;

    protected function setUp(): void
    {
        $this->filters = new HighlightFilters();
    }

    public function test_no_filters_returns_input_unchanged(): void
    {
        $input = [
            ['screen_name' => 'a', 'text' => 'foo'],
            ['screen_name' => 'b', 'text' => 'bar'],
        ];
        self::assertSame($input, $this->filters->apply($input, []));
    }

    public function test_distinct_sources_dedups_by_screen_name_keeping_first(): void
    {
        $input = [
            ['screen_name' => 'a', 'text' => 'first-a'],
            ['screen_name' => 'b', 'text' => 'first-b'],
            ['screen_name' => 'a', 'text' => 'second-a'],
        ];
        $output = $this->filters->apply($input, ['distinctSources' => true]);

        self::assertCount(2, $output);
        self::assertSame('first-a', $output[0]['text']);
        self::assertSame('first-b', $output[1]['text']);
    }

    public function test_term_does_case_insensitive_substring_match(): void
    {
        $input = [
            ['screen_name' => 'a', 'text' => 'About politics'],
            ['screen_name' => 'b', 'text' => 'About sport'],
        ];
        $output = $this->filters->apply($input, ['term' => 'POLITICS']);

        self::assertCount(1, $output);
        self::assertSame('a', $output[0]['screen_name']);
    }

    public function test_selected_aggregates_filters_to_listed_aggregates_only(): void
    {
        $input = [
            ['screen_name' => 'a', 'aggregate' => 'cultural'],
            ['screen_name' => 'b', 'aggregate' => 'political'],
            ['screen_name' => 'c', 'aggregate' => 'cultural'],
        ];
        $output = $this->filters->apply($input, ['selectedAggregates' => ['cultural']]);

        self::assertCount(2, $output);
    }
}
