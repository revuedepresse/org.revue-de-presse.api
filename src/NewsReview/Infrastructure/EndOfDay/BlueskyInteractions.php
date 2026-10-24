<?php
declare(strict_types=1);

namespace App\NewsReview\Infrastructure\EndOfDay;

use App\NewsReview\Domain\EndOfDay\Interactions;
use App\NewsReview\Domain\EndOfDay\Tid;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Current counts and like times from Bluesky's public API; repost times from
 * Constellation, a public index of links between AT Protocol records, since
 * Bluesky's API returns reposters without the time they reposted.
 */
final class BlueskyInteractions implements Interactions
{
    private const APP_VIEW = 'https://public.api.bsky.app/xrpc/';
    private const CONSTELLATION = 'https://constellation.microcosm.blue/links';
    private const USER_AGENT = 'revue-de-presse.org end-of-day rebuild (+https://revue-de-presse.org)';
    private const ATTEMPTS = 4;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        // Pause between requests, to stay well within public rate limits.
        private readonly int $pauseMicroseconds = 200_000,
    ) {
    }

    public function currentCounts(array $uris): array
    {
        $counts = [];
        foreach (array_chunk($uris, 25) as $chunk) {
            $query = implode('&', array_map(static fn(string $uri): string => 'uris=' . rawurlencode($uri), $chunk));
            $body = $this->get(self::APP_VIEW . 'app.bsky.feed.getPosts?' . $query);
            foreach ($body['posts'] ?? [] as $post) {
                $counts[(string) $post['uri']] = [
                    'reposts' => (int) ($post['repostCount'] ?? 0),
                    'likes' => (int) ($post['likeCount'] ?? 0),
                ];
            }
        }

        return $counts;
    }

    public function repostTimes(string $uri): array
    {
        $times = [];
        $cursor = null;
        do {
            $body = $this->get(self::CONSTELLATION . '?' . http_build_query(array_filter([
                'target' => $uri,
                'collection' => 'app.bsky.feed.repost',
                'path' => '.subject.uri',
                'limit' => 100,
                'cursor' => $cursor,
            ])));
            $records = $body['linking_records'] ?? [];
            foreach ($records as $record) {
                $times[] = Tid::createdAt((string) ($record['rkey'] ?? ''));
            }
            $cursor = $body['cursor'] ?? null;
        } while ($cursor !== null && $cursor !== '' && $records !== []);

        return $times;
    }

    public function likeTimes(string $uri): array
    {
        $times = [];
        $cursor = null;
        do {
            $body = $this->get(self::APP_VIEW . 'app.bsky.feed.getLikes?' . http_build_query(array_filter([
                'uri' => $uri,
                'limit' => 100,
                'cursor' => $cursor,
            ])));
            $likes = $body['likes'] ?? [];
            foreach ($likes as $like) {
                $times[] = new \DateTimeImmutable((string) $like['createdAt']);
            }
            $cursor = $body['cursor'] ?? null;
        } while ($cursor !== null && $cursor !== '' && $likes !== []);

        return $times;
    }

    /** @return array<string, mixed> */
    private function get(string $url): array
    {
        $failure = null;
        for ($attempt = 1; $attempt <= self::ATTEMPTS; $attempt++) {
            if ($this->pauseMicroseconds > 0) {
                usleep($this->pauseMicroseconds * $attempt);
            }
            try {
                $response = $this->httpClient->request('GET', $url, [
                    'headers' => ['User-Agent' => self::USER_AGENT, 'Accept' => 'application/json'],
                    'timeout' => 30,
                ]);
                $status = $response->getStatusCode();
                if ($status === 429 || $status >= 500) {
                    $failure = new \RuntimeException(sprintf('HTTP %d from %s', $status, strtok($url, '?')));
                    continue;
                }

                return $response->toArray();
            } catch (ExceptionInterface $exception) {
                $failure = $exception;
            }
        }

        throw new \RuntimeException(sprintf('Gave up on %s after %d attempts.', strtok($url, '?'), self::ATTEMPTS), 0, $failure);
    }
}
