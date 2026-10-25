<?php
declare(strict_types=1);

namespace App\Tests\Newsletter\Infrastructure\Http;

use App\Newsletter\Infrastructure\Http\PublicRateLimitListener;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\RateLimiter\Storage\InMemoryStorage;

final class PublicRateLimitListenerTest extends TestCase
{
    public function test_subscribe_post_uses_its_own_bucket(): void
    {
        $listener = new PublicRateLimitListener(
            newsletterPublicLimiter: $this->factory('newsletter_public', 100),
            newsletterUnsubscribePostLimiter: $this->factory('newsletter_unsubscribe_post', 100),
            newsletterSubscribePostLimiter: $this->factory('newsletter_subscribe_post', 2),
        );

        foreach ([1, 2] as $_) {
            $event = $this->event('POST', '/newsletter/subscribe');
            $listener($event);
            self::assertNull($event->getResponse());
        }

        $third = $this->event('POST', '/newsletter/subscribe');
        $listener($third);
        self::assertSame(429, $third->getResponse()?->getStatusCode());

        $confirm = $this->event('GET', '/newsletter/confirm/' . str_repeat('A', 43));
        $listener($confirm);
        self::assertNull($confirm->getResponse(), 'Other public routes keep their own budget');
    }

    public function test_subscribe_get_is_not_charged_to_the_post_bucket(): void
    {
        $listener = new PublicRateLimitListener(
            newsletterPublicLimiter: $this->factory('newsletter_public', 100),
            newsletterUnsubscribePostLimiter: $this->factory('newsletter_unsubscribe_post', 100),
            newsletterSubscribePostLimiter: $this->factory('newsletter_subscribe_post', 1),
        );

        foreach ([1, 2] as $_) {
            $event = $this->event('GET', '/newsletter/subscribe');
            $listener($event);
            self::assertNull($event->getResponse());
        }
    }

    private function factory(string $id, int $limit): RateLimiterFactory
    {
        return new RateLimiterFactory(
            ['id' => $id, 'policy' => 'fixed_window', 'limit' => $limit, 'interval' => '1 minute'],
            new InMemoryStorage(),
        );
    }

    private function event(string $method, string $path): RequestEvent
    {
        $request = Request::create($path, $method);
        $request->server->set('REMOTE_ADDR', '203.0.113.5');

        return new RequestEvent($this->createMock(HttpKernelInterface::class), $request, HttpKernelInterface::MAIN_REQUEST);
    }
}
