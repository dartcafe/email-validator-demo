<?php

declare(strict_types=1);

namespace Dartcafe\EmailValidator\Demo\Controller;

use Dartcafe\EmailValidator\Demo\Http\Request;
use Dartcafe\EmailValidator\Demo\Http\Response;
use Dartcafe\EmailValidator\Demo\Service\SuggestionStore;

final class SuggestionsController
{
    public function __construct(private SuggestionStore $store)
    {
    }

    /** @psalm-suppress PossiblyUnusedReturnValue */
    public function get(Request $_r): Response
    {
        $domains = $this->store->load();
        return Response::json(200, [
            'path'    => 'config/suggestions.txt',
            'count'   => count($domains),
            'domains' => $domains,
        ]);
    }

    /** @psalm-suppress PossiblyUnusedReturnValue */
    public function save(Request $r): Response
    {
        if ($r->json === null) {
            return Response::json(415, ['error' => 'Use application/json']);
        }
        /** @var list<string>|string|null $domains */
        $domains = $r->json['domains'] ?? null;
        /** @var string|null $content */
        $content = $r->json['content'] ?? null;

        if (is_string($content)) {
            $list = preg_split('/\R/u', $content) ?: [];
            $list = array_values(array_filter(array_map('trim', $list), fn ($x) => $x !== '' && $x[0] !== '#'));
        } elseif (is_array($domains)) {
            $list = array_map(static fn ($x) => $x, $domains);
        } else {
            return Response::json(400, ['error' => 'Provide "domains": string[] or "content": string']);
        }

        $this->store->save($list);
        return Response::json(200, ['status' => 'ok', 'count' => count($list)]);
    }
}
