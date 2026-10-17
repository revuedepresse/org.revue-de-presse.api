<?php
declare(strict_types=1);

namespace App\Newsletter\Infrastructure\Http;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\RateLimiter\RateLimiterFactory;

final class PublicRateLimitListener
{
    public function __construct(
        private readonly RateLimiterFactory $newsletterPublicLimiter,
        private readonly RateLimiterFactory $newsletterUnsubscribePostLimiter,
        private readonly RateLimiterFactory $newsletterSubscribePostLimiter,
    ) {}

    public function __invoke(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }
        $request = $event->getRequest();
        if (!str_starts_with($request->getPathInfo(), '/newsletter/')) {
            return;
        }
        $key = $request->getClientIp() ?: '0.0.0.0';
        $factory = match (true) {
            $request->isMethod('POST') && str_contains($request->getPathInfo(), '/unsubscribe/') => $this->newsletterUnsubscribePostLimiter,
            $request->isMethod('POST') && $request->getPathInfo() === '/newsletter/subscribe' => $this->newsletterSubscribePostLimiter,
            default => $this->newsletterPublicLimiter,
        };
        $limit = $factory->create($key)->consume();
        if (!$limit->isAccepted()) {
            $retryAfter = max(1, $limit->getRetryAfter()->getTimestamp() - time());
            $event->setResponse(new Response('Too Many Requests', 429, ['Retry-After' => (string) $retryAfter]));
        }
    }
}
