<?php

declare(strict_types=1);

namespace Dartcafe\EmailValidator\Demo;

use Dartcafe\EmailValidator\Demo\Controller\{ListsController, PagesController, ValidationController};
use Dartcafe\EmailValidator\Demo\Http\{Request, Response, Router};
use Dartcafe\EmailValidator\Demo\Service\{ArchiveService, ListStore, RateLimiter};
use Dartcafe\EmailValidator\Lists\ListManager;

/**
 * The main application class that sets up routing and controllers.
 */
final class App
{
    private Router $router;

    public function __construct(private string $configDir)
    {
        $this->router = new Router();

        $store = new ListStore($configDir);
        $archive = new ArchiveService($store);
        $lists = is_file($store->iniPath()) ? ListManager::fromIni($store->iniPath()) : null;

        // Configure the rate limiter
        $cap    = (int)($_ENV['RATE_CAPACITY'] ?? getenv('RATE_CAPACITY') ?: 60);   // Tokens in bucket
        $refill = (float)($_ENV['RATE_REFILL'] ?? getenv('RATE_REFILL') ?: 1.0); // Refill tokens/sec
        $mode   = (string)($_ENV['RATE_MODE'] ?? getenv('RATE_MODE') ?: 'global'); // 'global' | 'ip'
        $limiter = new RateLimiter('validate', $cap, $refill, sys_get_temp_dir() . '/email-validator-rate');

        $keyProvider = $mode === 'ip'
            ? (static fn (Request $r): string => 'ip:' . $r->ip)
            : (static fn (): string => 'global');

        $pages = new PagesController();
        $validation = new ValidationController($lists, $limiter, $keyProvider);

        $listsCtl = new ListsController($store, $archive);

        $this->router->get('/', fn (Request $r) => $pages->home($r));
        $this->router->get('/validate', fn (Request $r) => $validation->handle($r));
        $this->router->post('/validate', fn (Request $r) => $validation->handle($r));

        $this->router->get('/lists', fn (Request $r) => $listsCtl->get($r));
        $this->router->post('/lists', fn (Request $r) => $listsCtl->save($r));
        $this->router->get('/lists/export', fn (Request $r) => $listsCtl->export($r));
        $this->router->post('/lists/import', fn (Request $r) => $listsCtl->import($r));
        $this->router->get('/docs', fn ($r) => new Response(302, '', ['Location' => '/docs/']));
    }

    public function handle(Request $r): Response
    {
        return $this->router->dispatch($r);
    }
}
