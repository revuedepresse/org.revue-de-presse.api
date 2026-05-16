<?php
declare(strict_types=1);

// FrankenPHP worker-mode entrypoint.
//
// Loaded once when the php-worker container's FrankenPHP runtime starts;
// then enters a request loop, keeping the Symfony Kernel + container warm
// across many HTTP requests. The FPM service container is unaffected and
// keeps using public/index.php as its per-request entrypoint.
//
// Wired via the FRANKENPHP_CONFIG env in
// provisioning/containers/docker-compose.yaml:
//   FRANKENPHP_CONFIG: 'worker /var/www/.../public/worker.php'

use App\Kernel;
use Symfony\Component\HttpFoundation\Request;

require dirname(__DIR__).'/config/bootstrap.php';

if (!\function_exists('frankenphp_handle_request')) {
    throw new \RuntimeException(
        'public/worker.php must be run as a FrankenPHP worker. '
        . 'Set FRANKENPHP_CONFIG="worker /path/to/public/worker.php" '
        . 'in the container env.'
    );
}

$kernel = new Kernel($_SERVER['APP_ENV'], (bool) ($_SERVER['APP_DEBUG'] ?? false));

$handler = static function () use ($kernel): void {
    $request  = Request::createFromGlobals();
    $response = $kernel->handle($request);
    $response->send();
    $kernel->terminate($request, $response);
};

// Worker request loop. frankenphp_handle_request() blocks until a request
// arrives, dispatches it through the handler closure, then returns true.
// It returns false when FrankenPHP wants the worker to shut down
// (SIGTERM, max_requests reached, etc.).
while (true) {
    if (!\frankenphp_handle_request($handler)) {
        break;
    }

    // Reset request-scoped services (Doctrine EntityManager,
    // Symfony container resettable services, profiler data store, etc.)
    // so request N+1 doesn't see leftover state from request N.
    $kernel->reset();

    // Reclaim cycle-reference memory so leaks don't compound over hours
    // of worker uptime.
    \gc_collect_cycles();
}
