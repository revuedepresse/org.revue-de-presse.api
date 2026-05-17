<?php
declare(strict_types=1);

namespace App\NewsReview\Infrastructure\ApiPlatform\Listener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class HighlightsCacheHeaderListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::RESPONSE => 'onKernelResponse'];
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $value = $event->getRequest()->attributes->get('_highlights_cache');
        if ($value === null) {
            return;
        }

        $event->getResponse()->headers->set('x-cache', (string) $value);
    }
}
