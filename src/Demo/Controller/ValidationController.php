<?php

declare(strict_types=1);

namespace Dartcafe\EmailValidator\Demo\Controller;

use Dartcafe\EmailValidator\Demo\Http\Request;
use Dartcafe\EmailValidator\Demo\Http\Response;
use Dartcafe\EmailValidator\Demo\Service\RateLimiter;
use Dartcafe\EmailValidator\EmailValidator;
use Dartcafe\EmailValidator\Lists\ListManager;

/**
 * Controller for /validate endpoint.
 *
 * GET /validate?email=...
 * POST /validate { "email": "..." }
 *
 * Rate-limited.
 */
final class ValidationController
{
    /**
     * @param ListManager|null $lists The ListManager instance or null if no lists are configured
     * @param RateLimiter      $limiter The rate limiter instance
     * @param \Closure         $rateKeyProvider A function that takes a Request and returns a string key for rate limiting
     */
    public function __construct(
        private ?ListManager $lists,
        private RateLimiter $limiter,
        private \Closure $rateKeyProvider,   // fn(Request): string
    ) {
    }

    /**
     * Handle GET /validate and POST /validate
     *
     * @param Request $r The HTTP request
     */
    public function handle(Request $r): Response
    {
        // single configurable bucket
        $key = ($this->rateKeyProvider)($r);           // "global" or "ip:1.2.3.4"
        [$ok, $retry] = $this->limiter->hit($key, 1);  // cost=1
        if (!$ok) {
            return new Response(
                429,
                json_encode(['error' => 'Too many requests', 'key' => $key, 'retryAfter' => (int)ceil($retry)], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                ['Content-Type' => 'application/json; charset=utf-8', 'Retry-After' => (string)max(1, (int)ceil($retry))],
            );
        }

        // existing validate logic
        $email = $r->method === 'GET' ? ($r->query['email'] ?? null) : ($r->json['email'] ?? null);
        if (!is_string($email) || $email === '') {
            return Response::json(400, ['error' => 'Missing or invalid "email"']);
        }

        $validator = new EmailValidator(lists: $this->lists);
        $res = $validator->validate($email);
        return Response::json(200, $res);
    }
}
