<?php
declare(strict_types=1);

namespace App\Tests\Security\Infrastructure\Http;

use App\Security\Infrastructure\Http\RateLimitSubscriber;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\RateLimiter\Policy\NoLimiter;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\RateLimiter\Storage\InMemoryStorage;

class RateLimitSubscriberTest extends TestCase
{
    public function test_does_nothing_when_disabled(): void
    {
        $subscriber = new RateLimitSubscriber(
            tokenMintLimiterFactory: $this->permissiveFactory('token_mint'),
            highlightsLimiterFactory: $this->tightFactory('highlights'),
            docsLimiterFactory: $this->tightFactory('docs'),
            logger: new NullLogger(),
            enabled: false,
        );

        $event = $this->makeEvent('GET', '/api/highlights');
        $subscriber->onKernelRequest($event);

        self::assertNull($event->getResponse());
    }

    public function test_skips_healthcheck(): void
    {
        $subscriber = new RateLimitSubscriber(
            tokenMintLimiterFactory: $this->permissiveFactory('token_mint'),
            highlightsLimiterFactory: $this->tightFactory('highlights'),
            docsLimiterFactory: $this->tightFactory('docs'),
            logger: new NullLogger(),
            enabled: true,
        );

        $event = $this->makeEvent('GET', '/api/healthcheck');
        $subscriber->onKernelRequest($event);

        self::assertNull($event->getResponse());
    }

    public function test_returns_429_when_highlights_limiter_rejects_after_burst(): void
    {
        // tight limit: 2/min sliding window. Third call from same IP fires 429.
        $factory = $this->tightFactory('highlights', limit: 2);
        $subscriber = new RateLimitSubscriber(
            tokenMintLimiterFactory: $this->permissiveFactory('token_mint'),
            highlightsLimiterFactory: $factory,
            docsLimiterFactory: $this->permissiveFactory('docs'),
            logger: new NullLogger(),
            enabled: true,
        );

        $first = $this->makeEvent('GET', '/api/highlights');
        $subscriber->onKernelRequest($first);
        self::assertNull($first->getResponse());

        $second = $this->makeEvent('GET', '/api/highlights');
        $subscriber->onKernelRequest($second);
        self::assertNull($second->getResponse());

        $third = $this->makeEvent('GET', '/api/highlights');
        $subscriber->onKernelRequest($third);

        $response = $third->getResponse();
        self::assertNotNull($response);
        self::assertSame(429, $response->getStatusCode());
        self::assertNotNull($response->headers->get('Retry-After'));
    }

    public function test_distinct_ips_have_independent_buckets(): void
    {
        $factory = $this->tightFactory('highlights', limit: 1);
        $subscriber = new RateLimitSubscriber(
            tokenMintLimiterFactory: $this->permissiveFactory('token_mint'),
            highlightsLimiterFactory: $factory,
            docsLimiterFactory: $this->permissiveFactory('docs'),
            logger: new NullLogger(),
            enabled: true,
        );

        $a = $this->makeEvent('GET', '/api/highlights', ip: '203.0.113.5');
        $subscriber->onKernelRequest($a);
        self::assertNull($a->getResponse());

        $b = $this->makeEvent('GET', '/api/highlights', ip: '203.0.113.6');
        $subscriber->onKernelRequest($b);
        self::assertNull($b->getResponse(), 'Second IP should have its own bucket');
    }

    private function permissiveFactory(string $name): RateLimiterFactory
    {
        return new RateLimiterFactory(
            ['id' => $name, 'policy' => 'no_limit'],
            new InMemoryStorage(),
        );
    }

    private function tightFactory(string $name, int $limit = 60): RateLimiterFactory
    {
        return new RateLimiterFactory(
            [
                'id'       => $name,
                'policy'   => 'fixed_window',
                'limit'    => $limit,
                'interval' => '1 minute',
            ],
            new InMemoryStorage(),
        );
    }

    private function makeEvent(string $method, string $path, string $ip = '203.0.113.5'): RequestEvent
    {
        $request = Request::create($path, $method);
        $request->server->set('REMOTE_ADDR', $ip);
        $kernel = $this->createMock(HttpKernelInterface::class);

        return new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);
    }
}
