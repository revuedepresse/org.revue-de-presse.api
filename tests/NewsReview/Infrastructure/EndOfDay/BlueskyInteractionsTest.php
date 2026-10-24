<?php
declare(strict_types=1);

namespace App\Tests\NewsReview\Infrastructure\EndOfDay;

use App\NewsReview\Infrastructure\EndOfDay\BlueskyInteractions;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class BlueskyInteractionsTest extends TestCase
{
    /** @var list<array{string, string, array<string, mixed>}> */
    private array $requests = [];

    private function client(callable $respond): BlueskyInteractions
    {
        $http = new MockHttpClient(function (string $method, string $url, array $options) use ($respond): MockResponse {
            $this->requests[] = [$method, $url, $options];

            return $respond($url);
        });

        return new BlueskyInteractions($http, pauseMicroseconds: 0);
    }

    private static function json(array $body, int $status = 200): MockResponse
    {
        return new MockResponse(json_encode($body, JSON_THROW_ON_ERROR), ['http_code' => $status]);
    }

    public function test_current_counts_are_read_25_posts_at_a_time(): void
    {
        $uris = array_map(static fn(int $i): string => "at://did:plc:x/app.bsky.feed.post/$i", range(1, 30));
        $interactions = $this->client(function (string $url): MockResponse {
            parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
            $requested = preg_match_all('/uris=([^&]+)/', (string) parse_url($url, PHP_URL_QUERY), $m) ? array_map('urldecode', $m[1]) : [];

            return self::json(['posts' => array_map(static fn(string $u): array => ['uri' => $u, 'repostCount' => 2, 'likeCount' => 5], $requested)]);
        });

        $counts = $interactions->currentCounts($uris);

        self::assertCount(30, $counts);
        self::assertSame(['reposts' => 2, 'likes' => 5], $counts[$uris[0]]);
        self::assertCount(2, $this->requests);
        self::assertStringStartsWith('https://public.api.bsky.app/xrpc/app.bsky.feed.getPosts?', $this->requests[0][1]);
    }

    public function test_repost_times_come_from_record_keys_across_pages(): void
    {
        $interactions = $this->client(function (string $url): MockResponse {
            return str_contains($url, 'cursor=page2')
                ? self::json(['total' => 3, 'linking_records' => [['did' => 'did:plc:c', 'collection' => 'app.bsky.feed.repost', 'rkey' => 'self']], 'cursor' => null])
                : self::json(['total' => 3, 'linking_records' => [
                    ['did' => 'did:plc:a', 'collection' => 'app.bsky.feed.repost', 'rkey' => '3mymn7flova2x'],
                    ['did' => 'did:plc:b', 'collection' => 'app.bsky.feed.repost', 'rkey' => '3mymlyae4kk2i'],
                ], 'cursor' => 'page2']);
        });

        $times = $interactions->repostTimes('at://did:plc:x/app.bsky.feed.post/1');

        self::assertSame(['2026-10-24 11:56:49', '2026-10-24 11:34:55', null], array_map(static fn(?\DateTimeImmutable $t): ?string => $t?->format('Y-m-d H:i:s'), $times));
        self::assertStringStartsWith('https://constellation.microcosm.blue/links?', $this->requests[0][1]);
        self::assertStringContainsString('collection=app.bsky.feed.repost', $this->requests[0][1]);
        self::assertStringContainsString('path=.subject.uri', $this->requests[0][1]);
    }

    public function test_like_times_come_from_get_likes_across_pages(): void
    {
        $interactions = $this->client(function (string $url): MockResponse {
            return str_contains($url, 'cursor=next')
                ? self::json(['likes' => [['createdAt' => '2026-10-23T21:59:00.000Z']]])
                : self::json(['likes' => [['createdAt' => '2026-10-23T10:00:00.000Z'], ['createdAt' => '2026-10-23T23:00:00Z']], 'cursor' => 'next']);
        });

        $times = $interactions->likeTimes('at://did:plc:x/app.bsky.feed.post/1');

        self::assertSame(['2026-10-23 10:00', '2026-10-23 23:00', '2026-10-23 21:59'], array_map(static fn(\DateTimeImmutable $t): string => $t->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i'), $times));
        self::assertStringStartsWith('https://public.api.bsky.app/xrpc/app.bsky.feed.getLikes?', $this->requests[0][1]);
    }

    public function test_identifies_itself_and_retries_transient_errors(): void
    {
        $calls = 0;
        $interactions = $this->client(function () use (&$calls): MockResponse {
            return ++$calls < 3 ? self::json(['error' => 'busy'], 503) : self::json(['likes' => []]);
        });

        self::assertSame([], $interactions->likeTimes('at://did:plc:x/app.bsky.feed.post/1'));
        self::assertSame(3, $calls);
        $headers = implode("\n", $this->requests[0][2]['headers'] ?? []);
        self::assertStringContainsString('revue-de-presse.org', $headers);
    }
}
