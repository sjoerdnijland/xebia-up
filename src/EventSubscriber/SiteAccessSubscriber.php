<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class SiteAccessSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::REQUEST => ['onKernelRequest', 20]];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $path = $request->getPathInfo();

        if (str_starts_with($path, '/__access')
            || str_starts_with($path, '/_wdt')
            || str_starts_with($path, '/_profiler')
            || str_starts_with($path, '/_error')
        ) {
            return;
        }

        if (!$request->hasSession()) {
            return;
        }

        if ($request->getSession()->get('site_access_granted')) {
            return;
        }

        $next = $request->getRequestUri();
        $event->setResponse(new RedirectResponse('/__access?next=' . rawurlencode($next)));
    }
}
