<?php
declare(strict_types=1);

namespace App\Tests\NewsReview\Infrastructure\ApiPlatform\State;

use ApiPlatform\Metadata\GetCollection;
use App\NewsReview\Domain\Model\HighlightDto;
use App\NewsReview\Domain\Snapshot\Filter\HighlightFilters;
use App\NewsReview\Domain\Snapshot\Filter\HighlightNormalizer;
use App\NewsReview\Infrastructure\ApiPlatform\State\HighlightCollectionProvider;
use App\Tests\NewsReview\Infrastructure\Repository\InMemorySnapshotReader;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class HighlightCollectionProviderTest extends TestCase
{
    public function test_returns_normalized_dtos_from_snapshot(): void
    {
        $reader = new InMemorySnapshotReader([
            '2026-05-01' => [
                'statuses' => [[
                    'screen_name'         => 'a',
                    'reposts'             => 1,
                    'likes'               => 2,
                    'text'                => 'hi',
                    'publication_id'      => 'at://did/x/p1',
                    'publicationDateTime' => '2026-05-01T10:00:00+02:00',
                ]],
            ],
        ]);
        $stack = new RequestStack();
        $stack->push(Request::create('/api/highlights?startDate=2026-05-01&endDate=2026-05-01&includeRetweets=0'));

        $provider = new HighlightCollectionProvider(
            $reader,
            new HighlightFilters(),
            new HighlightNormalizer(),
            null,
            $stack,
            new NullLogger(),
            apiEnv: 'test',
        );

        $items = iterator_to_array($provider->provide(new GetCollection(uriTemplate: '/highlights')));

        self::assertCount(1, $items);
        self::assertInstanceOf(HighlightDto::class, $items[0]);
        self::assertSame('a', $items[0]->screenName);
    }

    public function test_empty_snapshot_returns_empty_collection(): void
    {
        $reader = new InMemorySnapshotReader();
        $stack  = new RequestStack();
        $stack->push(Request::create('/api/highlights?startDate=1999-01-01&endDate=1999-01-01&includeRetweets=0'));

        $provider = new HighlightCollectionProvider(
            $reader,
            new HighlightFilters(),
            new HighlightNormalizer(),
            null,
            $stack,
            new NullLogger(),
            apiEnv: 'test',
        );

        $items = iterator_to_array($provider->provide(new GetCollection(uriTemplate: '/highlights')));

        self::assertSame([], $items);
    }

    public function test_x_benchmark_bypasses_redis_in_non_prod(): void
    {
        $reader = new InMemorySnapshotReader([
            '2026-05-01' => ['statuses' => []],
        ]);
        $request = Request::create('/api/highlights?startDate=2026-05-01&endDate=2026-05-01&includeRetweets=0');
        $request->headers->set('x-benchmark', '1');
        $stack = new RequestStack();
        $stack->push($request);

        $provider = new HighlightCollectionProvider(
            $reader,
            new HighlightFilters(),
            new HighlightNormalizer(),
            null,
            $stack,
            new NullLogger(),
            apiEnv: 'test',
        );

        $provider->provide(new GetCollection(uriTemplate: '/highlights'));

        self::assertSame('bypass', $request->attributes->get('_highlights_cache'));
    }
}
