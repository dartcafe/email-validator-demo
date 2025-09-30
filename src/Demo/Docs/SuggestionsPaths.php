<?php

declare(strict_types=1);

namespace Dartcafe\EmailValidator\Demo\Docs;

use OpenApi\Attributes as OA;

final class SuggestionsPaths
{
    #[OA\Get(
        path: '/suggestions',
        operationId: 'suggestionsGet',
        tags: ['suggestions'],
        summary: 'Get the suggestion domains',
        responses: [
            new OA\Response(
                response: 200,
                description: 'OK',
                content: new OA\JsonContent(ref: '#/components/schemas/SuggestionsGetResponse'),
            ),
        ],
    )]
    public function get(): void
    {
    }

    #[OA\Post(
        path: '/suggestions',
        operationId: 'suggestionsSave',
        tags: ['suggestions'],
        summary: 'Save the suggestion domains',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/SuggestionsSaveRequest'),
        ),
        responses: [
            new OA\Response(response: 200, description: 'Saved', content: new OA\JsonContent(
                properties: [ new OA\Property(property: 'status', type: 'string', example: 'ok'),
                              new OA\Property(property: 'count', type: 'integer', example: 3) ],
                type: 'object',
            )),
            new OA\Response(response: 415, description: 'Unsupported Media Type'),
        ],
    )]
    public function save(): void
    {
    }
}
