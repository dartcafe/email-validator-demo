<?php

declare(strict_types=1);

namespace Dartcafe\EmailValidator\Demo\Controller;

use Dartcafe\EmailValidator\Contracts\DomainSuggestionProvider;
use Dartcafe\EmailValidator\Contracts\ListProvider;
use Dartcafe\EmailValidator\Demo\Http\Request;
use Dartcafe\EmailValidator\Demo\Http\Response;
use Dartcafe\EmailValidator\Demo\Service\RateLimiter;
use Dartcafe\EmailValidator\EmailValidator;

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
    /** @var null|callable():(?ListProvider) */
    private $listsFactory;
    /** @var callable():DomainSuggestionProvider */
    private $suggestionsFactory;
    /**
     * @param null|callable():(?ListProvider)     $listsFactory A function that returns a ListProvider or null
     * @param callable():DomainSuggestionProvider $suggestionsFactory
     * @param RateLimiter                         $limiter The rate limiter instance
     * @param \Closure                            $rateKeyProvider A function that takes a Request and returns a string key for rate limiting
     */
    public function __construct(
        ?callable $listsFactory,
        callable $suggestionsFactory,
        private RateLimiter $limiter,
        private \Closure $rateKeyProvider,
    ) {
        $this->listsFactory       = $listsFactory;
        $this->suggestionsFactory = $suggestionsFactory;
    }

    /**
     * Handle GET /validate and POST /validate
     *
     * @param Request $r The HTTP request
     */
    public function handle(Request $r): Response
    {
        $lists = $this->listsFactory ? ($this->listsFactory)() : null;
        $suggestions = ($this->suggestionsFactory)();
        // single configurable bucket
        /** @var string $key */
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

        $validator = new EmailValidator($suggestions, $lists);
        $res = $validator->validate($email);
        return Response::json(200, $res);
    }
}
