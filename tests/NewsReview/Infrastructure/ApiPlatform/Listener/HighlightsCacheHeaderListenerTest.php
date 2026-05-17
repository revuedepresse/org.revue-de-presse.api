<?php
declare(strict_types=1);

namespace App\Tests\NewsReview\Infrastructure\ApiPlatform\Listener;

use App\NewsReview\Infrastructure\ApiPlatform\Listener\HighlightsCacheHeaderListener;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class HighlightsCacheHeaderListenerTest extends TestCase
{
    public function test_copies_x_cache_attribute_to_response_header(): void
    {
        $listener = new HighlightsCacheHeaderListener();
        $request = Request::create('/api/highlights');
        $request->attributes->set('_highlights_cache', 'hit');
        $response = new Response('', 200);

        $kernel = $this->createMock(HttpKernelInterface::class);
        $listener->onKernelResponse(new ResponseEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, $response));

        self::assertSame('hit', $response->headers->get('x-cache'));
    }

    public function test_no_header_when_attribute_absent(): void
    {
        $listener = new HighlightsCacheHeaderListener();
        $request = Request::create('/api/highlights');
        $response = new Response('', 200);

        $kernel = $this->createMock(HttpKernelInterface::class);
        $listener->onKernelResponse(new ResponseEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, $response));

        self::assertFalse($response->headers->has('x-cache'));
    }
}
