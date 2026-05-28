<?php
declare(strict_types=1);

namespace App\Security\Infrastructure\Http;

use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\RateLimiter\RateLimit;
use Symfony\Component\RateLimiter\RateLimiterFactory;

final class RateLimitSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly RateLimiterFactory $tokenMintLimiterFactory,
        private readonly RateLimiterFactory $deviceTokenMintLimiterFactory,
        private readonly RateLimiterFactory $highlightsLimiterFactory,
        private readonly RateLimiterFactory $docsLimiterFactory,
        private readonly LoggerInterface $logger,
        private readonly bool $enabled,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::REQUEST => ['onKernelRequest', 8]];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$this->enabled || !$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $factory = $this->selectFactory($request);
        if ($factory === null) {
            return;
        }

        $key = $this->ipKey($request);

        try {
            $limit = $factory->create($key)->consume(1);
        } catch (\Throwable $exception) {
            $this->logger->warning('rate-limit-fail-open', [
                'path' => $request->getPathInfo(),
                'error' => $exception->getMessage(),
            ]);

            return;
        }

        if (!$limit->isAccepted()) {
            $event->setResponse($this->rejectionResponse($request, $limit));
        }
    }

    private function selectFactory(Request $request): ?RateLimiterFactory
    {
        $path = $request->getPathInfo();
        if ($path === '/api/healthcheck') {
            return null;
        }
        if ($path === '/api/token') {
            return $this->tokenMintLimiterFactory;
        }
        if ($path === '/api/device-tokens') {
            return $this->deviceTokenMintLimiterFactory;
        }
        if (str_starts_with($path, '/api/docs')) {
            return $this->docsLimiterFactory;
        }
        if (str_starts_with($path, '/api/')) {
            return $this->highlightsLimiterFactory;
        }

        return null;
    }

    private function ipKey(Request $request): string
    {
        $ip = (string) $request->getClientIp();
        if ($ip === '') {
            return 'ip:unknown';
        }
        if (str_contains($ip, ':')) {
            $parts = explode(':', $ip);
            return 'ip6:' . implode(':', array_slice($parts, 0, 4));
        }

        return 'ip4:' . $ip;
    }

    private function rejectionResponse(Request $request, RateLimit $limit): JsonResponse
    {
        $retryAfter = max(1, $limit->getRetryAfter()->getTimestamp() - time());

        return new JsonResponse(
            [
                'type'   => 'https://tools.ietf.org/html/rfc6585#section-4',
                'title'  => 'Too Many Requests',
                'status' => 429,
                'detail' => sprintf('Rate limit exceeded for %s', $request->getPathInfo()),
            ],
            429,
            [
                'Content-Type'        => 'application/problem+json',
                'Retry-After'         => (string) $retryAfter,
                'RateLimit-Limit'     => (string) $limit->getLimit(),
                'RateLimit-Remaining' => '0',
                'RateLimit-Reset'     => (string) $retryAfter,
            ],
        );
    }
}
