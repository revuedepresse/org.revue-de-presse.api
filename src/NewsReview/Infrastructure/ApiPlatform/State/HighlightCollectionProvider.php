<?php
declare(strict_types=1);

namespace App\NewsReview\Infrastructure\ApiPlatform\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\NewsReview\Domain\Resource\HighlightDto;
use App\NewsReview\Domain\Snapshot\Filter\HighlightFilters;
use App\NewsReview\Domain\Snapshot\Filter\HighlightNormalizer;
use App\NewsReview\Domain\Snapshot\SnapshotReader;
use App\NewsReview\Infrastructure\Repository\CacheKey\HighlightCacheKey;
use App\Infrastructure\Cache\RedisCache;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @implements ProviderInterface<HighlightDto>
 */
final class HighlightCollectionProvider implements ProviderInterface
{
    public function __construct(
        private readonly SnapshotReader $reader,
        private readonly HighlightFilters $filters,
        private readonly HighlightNormalizer $normalizer,
        private readonly ?RedisCache $redis,
        private readonly RequestStack $requestStack,
        private readonly LoggerInterface $logger,
        private readonly string $apiEnv,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): iterable
    {
        $request = $this->requestStack->getCurrentRequest();
        $query = $request !== null ? $request->query->all() : [];
        $params = $this->parseQuery($query);

        $bypass = $request !== null && $request->headers->has('x-benchmark') && $this->apiEnv !== 'prod';

        if ($bypass || $this->redis === null) {
            $this->setCacheLabel($bypass ? 'bypass' : 'unknown');

            return $this->loadFresh($params);
        }

        $key = 'highlights:items:' . HighlightCacheKey::from($params);

        try {
            $client = $this->redis->getClient();
            $cached = $client->get($key);
            if ($cached !== null && $cached !== false) {
                $this->setCacheLabel('hit');
                $decoded = json_decode((string) $cached, true);

                return $this->dtosFromRaw(is_array($decoded) ? $decoded : []);
            }
            $this->setCacheLabel('miss');
            $fresh = iterator_to_array($this->loadFresh($params));
            $rawForCache = array_map(static fn(HighlightDto $d): array => (array) $d, $fresh);
            $client->setex($key, 3600, json_encode($rawForCache, JSON_THROW_ON_ERROR));

            return $fresh;
        } catch (\Throwable $exception) {
            $this->logger->warning('redis read-through unavailable', ['error' => $exception->getMessage()]);
            $this->setCacheLabel('error');

            return $this->loadFresh($params);
        }
    }

    private function loadFresh(array $params): iterable
    {
        $date = $params['startDate'] instanceof \DateTimeInterface
            ? $params['startDate']->format('Y-m-d')
            : 'unknown';
        $snapshot = $this->reader->read($date);
        $statuses = $snapshot['statuses'] ?? $snapshot;
        if (!is_array($statuses)) {
            return [];
        }
        $filtered = $this->filters->apply(array_values($statuses), $params);

        return array_map(fn(array $raw): HighlightDto => $this->normalizer->toDto($raw), $filtered);
    }

    private function dtosFromRaw(array $rows): array
    {
        return array_map(
            function (array $row): HighlightDto {
                $date = $row['date'] ?? null;
                if ($date instanceof \DateTimeImmutable) {
                    $dt = $date;
                } elseif (is_array($date) && isset($date['date'])) {
                    $dt = new \DateTimeImmutable($date['date']);
                } else {
                    $dt = new \DateTimeImmutable((string) ($date ?? 'now'));
                }

                return new HighlightDto(
                    publicationId: (string) ($row['publicationId'] ?? ''),
                    screenName:    (string) ($row['screenName'] ?? ''),
                    avatarUrl:     isset($row['avatarUrl']) ? (string) $row['avatarUrl'] : null,
                    text:          (string) ($row['text'] ?? ''),
                    reposts:       (int) ($row['reposts'] ?? 0),
                    likes:         (int) ($row['likes'] ?? 0),
                    replies:       (int) ($row['replies'] ?? 0),
                    date:          $dt,
                    url:           (string) ($row['url'] ?? ''),
                );
            },
            $rows,
        );
    }

    private function parseQuery(array $query): array
    {
        $parsed = $query;
        foreach (['startDate', 'endDate'] as $field) {
            if (isset($query[$field]) && is_string($query[$field])) {
                try {
                    $parsed[$field] = (new \DateTime($query[$field], new \DateTimeZone('Europe/Paris')))->setTime(0, 1);
                } catch (\Throwable) {
                    $parsed[$field] = null;
                }
            }
        }

        return $parsed;
    }

    private function setCacheLabel(string $label): void
    {
        $request = $this->requestStack->getCurrentRequest();
        if ($request !== null) {
            $request->attributes->set('_highlights_cache', $label);
        }
    }
}
